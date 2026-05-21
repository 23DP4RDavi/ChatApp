<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\GroupChannel;
use App\Models\GroupMemberRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class GroupChannelController extends Controller
{
    private function ensureGroupSchema(): bool
    {
        try {
            if (!Schema::hasTable('group_channels')) {
                Schema::create('group_channels', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
                    $table->string('name');
                    $table->string('type')->default('text');
                    $table->integer('position')->default(0);
                    $table->timestamps();
                });
            }

            if (!Schema::hasColumn('group_channels', 'category')) {
                Schema::table('group_channels', function (Blueprint $table) {
                    $table->string('category')->default('Text Channels')->after('type');
                });
            }

            if (!Schema::hasColumn('group_channels', 'allowed_role_ids')) {
                Schema::table('group_channels', function (Blueprint $table) {
                    $table->json('allowed_role_ids')->nullable()->after('category');
                });
            }

            if (!Schema::hasTable('group_roles')) {
                Schema::create('group_roles', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
                    $table->string('name');
                    $table->string('color')->default('#99aab5');
                    $table->json('permissions')->nullable();
                    $table->integer('position')->default(0);
                    $table->boolean('is_default')->default(false);
                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('group_member_roles')) {
                Schema::create('group_member_roles', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
                    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                    $table->foreignId('role_id')->constrained('group_roles')->onDelete('cascade');
                    $table->timestamps();
                    $table->unique(['conversation_id', 'user_id', 'role_id']);
                });
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function hasChannelsTable(): bool
    {
        return Schema::hasTable('group_channels');
    }

    private function hasMemberRolesTable(): bool
    {
        return Schema::hasTable('group_member_roles');
    }

    private function hasOwnerColumn(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $cached = Schema::hasColumn('conversations', 'owner_id');
        return $cached;
    }

    private function getGroupOrFail($groupId)
    {
        $conv = Conversation::findOrFail($groupId);
        abort_unless($conv->type === 'group', 404);
        abort_unless($conv->participants()->where('user_id', Auth::id())->exists(), 403, 'Not a member');
        return $conv;
    }

    private function assertOwner($conv)
    {
        if (!$this->hasOwnerColumn()) {
            return;
        }

        if (empty($conv->owner_id)) {
            try {
                $conv->owner_id = Auth::id();
                $conv->save();
            } catch (\Throwable $e) {
                // Fall through to authorization check below.
            }
        }

        abort_unless((int) $conv->owner_id === (int) Auth::id(), 403, 'Only the server owner can do this');
    }

    public function index($groupId)
    {
        $conv = $this->getGroupOrFail($groupId);

        if (!$this->ensureGroupSchema()) {
            return response()->json([
                'channels' => [],
                'message' => 'Channels are not available yet. Schema initialization failed.',
            ], 503);
        }

        if (!$this->hasChannelsTable()) {
            return response()->json([
                'channels' => [],
                'message' => 'Channels are not available yet. Run database migrations.',
            ]);
        }

        $channels = $conv->channels()->orderBy('position')->get();
        $hasAllowedRoleIds = Schema::hasColumn('group_channels', 'allowed_role_ids');
        $isOwner = !$this->hasOwnerColumn() || (int) $conv->owner_id === (int) Auth::id();

        // Owners can see all channels; members only see channels they have role access to
        if (!$isOwner && $hasAllowedRoleIds && $this->hasMemberRolesTable()) {
            $userRoleIds = GroupMemberRole::where('conversation_id', $groupId)
                ->where('user_id', Auth::id())
                ->pluck('role_id')
                ->toArray();

            $channels = $channels->filter(function ($channel) use ($userRoleIds) {
                $allowed = $channel->allowed_role_ids ?? [];
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

        if (!$this->ensureGroupSchema()) {
            return response()->json(['message' => 'Channels are not available yet. Schema initialization failed.'], 503);
        }

        if (!$this->hasChannelsTable()) {
            return response()->json(['message' => 'Channels are not available yet. Run database migrations.'], 503);
        }

        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'type'     => 'in:text,announcement',
            'category' => 'sometimes|string|max:100',
        ]);

        $position = (int) $conv->channels()->max('position') + 1;

        $payload = [
            'conversation_id' => $groupId,
            'name' => strtolower(preg_replace('/\s+/', '-', trim($data['name']))),
            'position' => $position,
        ];

        if (Schema::hasColumn('group_channels', 'type')) {
            $payload['type'] = $data['type'] ?? 'text';
        }

        if (Schema::hasColumn('group_channels', 'category')) {
            $payload['category'] = $data['category'] ?? 'Text Channels';
        }

        try {
            $channel = GroupChannel::create($payload);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to create channel.'], 500);
        }

        return response()->json(['channel' => $channel], 201);
    }

    public function update(Request $request, $groupId, $channelId)
    {
        $conv = $this->getGroupOrFail($groupId);
        $this->assertOwner($conv);

        if (!$this->ensureGroupSchema()) {
            return response()->json(['message' => 'Channels are not available yet. Schema initialization failed.'], 503);
        }

        if (!$this->hasChannelsTable()) {
            return response()->json(['message' => 'Channels are not available yet. Run database migrations.'], 503);
        }

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

        if (!Schema::hasColumn('group_channels', 'type')) {
            unset($data['type']);
        }

        if (!Schema::hasColumn('group_channels', 'category')) {
            unset($data['category']);
        }

        if (!Schema::hasColumn('group_channels', 'allowed_role_ids')) {
            unset($data['allowed_role_ids']);
        }

        try {
            $channel->update($data);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to update channel.'], 500);
        }

        return response()->json(['channel' => $channel]);
    }

    public function reorder(Request $request, $groupId)
    {
        $conv = $this->getGroupOrFail($groupId);
        $this->assertOwner($conv);

        if (!$this->ensureGroupSchema()) {
            return response()->json(['message' => 'Channels are not available yet. Schema initialization failed.'], 503);
        }

        if (!$this->hasChannelsTable()) {
            return response()->json(['message' => 'Channels are not available yet. Run database migrations.'], 503);
        }

        $validated = $request->validate([
            'channels'             => 'required|array',
            'channels.*.id'        => 'required|integer',
            'channels.*.position'  => 'required|integer|min:0',
            'channels.*.category'  => 'required|string|max:100',
        ]);

        foreach ($validated['channels'] as $item) {
            $update = [
                'position' => $item['position'],
            ];

            if (Schema::hasColumn('group_channels', 'category')) {
                $update['category'] = $item['category'];
            }

            GroupChannel::where('id', $item['id'])
                ->where('conversation_id', $groupId)
                ->update($update);
        }

        return response()->json([
            'channels' => $conv->channels()->orderBy('position')->get(),
        ]);
    }

    public function destroy($groupId, $channelId)
    {
        $conv = $this->getGroupOrFail($groupId);
        $this->assertOwner($conv);

        if (!$this->ensureGroupSchema()) {
            return response()->json(['message' => 'Channels are not available yet. Schema initialization failed.'], 503);
        }

        if (!$this->hasChannelsTable()) {
            return response()->json(['message' => 'Channels are not available yet. Run database migrations.'], 503);
        }

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

        $this->ensureGroupSchema();

        if (!$this->hasMemberRolesTable()) {
            $members = $conv->users()->with([])->get()->map(function ($user) use ($conv) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'avatar_thumbnail' => $user->avatar_thumbnail,
                    'is_owner' => $this->hasOwnerColumn() ? ((int) $conv->owner_id === (int) $user->id) : false,
                    'roles' => [],
                ];
            });

            return response()->json(['members' => $members]);
        }

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
                'is_owner' => $this->hasOwnerColumn() ? ((int) $conv->owner_id === (int) $user->id) : false,
                'roles' => $memberRoles->values(),
            ];
        });

        return response()->json(['members' => $members]);
    }
}
