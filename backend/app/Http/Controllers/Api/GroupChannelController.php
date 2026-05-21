<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\GroupChannel;
use App\Models\GroupMemberRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupChannelController extends Controller
{
    private function getGroupOrFail($groupId)
    {
        $conv = Conversation::findOrFail($groupId);
        abort_unless($conv->type === 'group', 404);
        abort_unless($conv->participants()->where('user_id', Auth::id())->exists(), 403, 'Not a member');
        return $conv;
    }

    private function assertOwner($conv)
    {
        abort_unless((int) $conv->owner_id === (int) Auth::id(), 403, 'Only the server owner can do this');
    }

    public function index($groupId)
    {
        $conv = $this->getGroupOrFail($groupId);
        $channels = $conv->channels()->orderBy('position')->get();

        // Owners can see all channels; members only see channels they have role access to
        if ((int) $conv->owner_id !== (int) Auth::id()) {
            $userRoleIds = GroupMemberRole::where('conversation_id', $groupId)
                ->where('user_id', Auth::id())
                ->pluck('role_id')
                ->toArray();

            $channels = $channels->filter(function ($channel) use ($userRoleIds) {
                $allowed = $channel->allowed_role_ids;
                // No restrictions — everyone can view
                if (empty($allowed)) return true;
                // User must have at least one allowed role
                return count(array_intersect($userRoleIds, $allowed)) > 0;
            })->values();
        }

        return response()->json(['channels' => $channels]);
    }

    public function store(Request $request, $groupId)
    {
        $conv = $this->getGroupOrFail($groupId);
        $this->assertOwner($conv);

        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'type'     => 'in:text,announcement',
            'category' => 'sometimes|string|max:100',
        ]);

        $position = $conv->channels()->max('position') + 1;
        $channel = GroupChannel::create([
            'conversation_id' => $groupId,
            'name' => strtolower(preg_replace('/\s+/', '-', trim($data['name']))),
            'type' => $data['type'] ?? 'text',
            'category' => $data['category'] ?? 'Text Channels',
            'position' => $position,
        ]);

        return response()->json(['channel' => $channel], 201);
    }

    public function update(Request $request, $groupId, $channelId)
    {
        $conv = $this->getGroupOrFail($groupId);
        $this->assertOwner($conv);

        $channel = GroupChannel::where('id', $channelId)
            ->where('conversation_id', $groupId)
            ->firstOrFail();

        $data = $request->validate([
            'name'             => 'sometimes|required|string|max:100',
            'type'             => 'sometimes|in:text,announcement',
            'category'         => 'sometimes|string|max:100',
            'allowed_role_ids' => 'sometimes|nullable|array',
            'allowed_role_ids.*' => 'integer',
        ]);

        if (isset($data['name'])) {
            $data['name'] = strtolower(preg_replace('/\s+/', '-', trim($data['name'])));
        }

        $channel->update($data);
        return response()->json(['channel' => $channel]);
    }

    public function reorder(Request $request, $groupId)
    {
        $conv = $this->getGroupOrFail($groupId);
        $this->assertOwner($conv);

        $validated = $request->validate([
            'channels'             => 'required|array',
            'channels.*.id'        => 'required|integer',
            'channels.*.position'  => 'required|integer|min:0',
            'channels.*.category'  => 'required|string|max:100',
        ]);

        foreach ($validated['channels'] as $item) {
            GroupChannel::where('id', $item['id'])
                ->where('conversation_id', $groupId)
                ->update([
                    'position' => $item['position'],
                    'category' => $item['category'],
                ]);
        }

        return response()->json([
            'channels' => $conv->channels()->orderBy('position')->get(),
        ]);
    }

    public function destroy($groupId, $channelId)
    {
        $conv = $this->getGroupOrFail($groupId);
        $this->assertOwner($conv);

        $channel = GroupChannel::where('id', $channelId)
            ->where('conversation_id', $groupId)
            ->firstOrFail();

        // Prevent deleting the last channel
        abort_if($conv->channels()->count() <= 1, 422, 'Cannot delete the last channel');

        $channel->delete();
        return response()->json(['message' => 'Channel deleted']);
    }

    public function members($groupId)
    {
        $conv = $this->getGroupOrFail($groupId);

        $members = $conv->users()->with([])->get()->map(function ($user) use ($conv) {
            $memberRoles = \App\Models\GroupMemberRole::with('role')
                ->where('conversation_id', $conv->id)
                ->where('user_id', $user->id)
                ->get()
                ->pluck('role')
                ->filter();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'avatar_thumbnail' => $user->avatar_thumbnail,
                'is_owner' => $conv->owner_id === $user->id,
                'roles' => $memberRoles->values(),
            ];
        });

        return response()->json(['members' => $members]);
    }
}
