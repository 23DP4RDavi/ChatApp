<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\GroupRole;
use App\Models\GroupMemberRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupRoleController extends Controller
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
        abort_unless($conv->owner_id === Auth::id(), 403, 'Only the server owner can do this');
    }

    public function index($groupId)
    {
        $conv = $this->getGroupOrFail($groupId);
        return response()->json(['roles' => $conv->roles()->orderBy('position')->get()]);
    }

    public function store(Request $request, $groupId)
    {
        $conv = $this->getGroupOrFail($groupId);
        $this->assertOwner($conv);

        $data = $request->validate([
            'name' => 'required|string|max:60',
            'color' => 'sometimes|string|regex:/^#[0-9a-fA-F]{6}$/',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'in:manage_channels,manage_roles,invite_members,send_messages,delete_messages,manage_members',
        ]);

        $position = $conv->roles()->max('position') + 1;
        $role = GroupRole::create([
            'conversation_id' => $groupId,
            'name' => $data['name'],
            'color' => $data['color'] ?? '#99aab5',
            'permissions' => $data['permissions'] ?? [],
            'position' => $position,
            'is_default' => false,
        ]);

        return response()->json(['role' => $role], 201);
    }

    public function update(Request $request, $groupId, $roleId)
    {
        $conv = $this->getGroupOrFail($groupId);
        $this->assertOwner($conv);

        $role = GroupRole::where('id', $roleId)
            ->where('conversation_id', $groupId)
            ->firstOrFail();

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:60',
            'color' => 'sometimes|string|regex:/^#[0-9a-fA-F]{6}$/',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'in:manage_channels,manage_roles,invite_members,send_messages,delete_messages,manage_members',
        ]);

        $role->update($data);
        return response()->json(['role' => $role]);
    }

    public function destroy($groupId, $roleId)
    {
        $conv = $this->getGroupOrFail($groupId);
        $this->assertOwner($conv);

        $role = GroupRole::where('id', $roleId)
            ->where('conversation_id', $groupId)
            ->firstOrFail();

        $role->delete();
        return response()->json(['message' => 'Role deleted']);
    }

    public function assign(Request $request, $groupId, $roleId, $userId)
    {
        $conv = $this->getGroupOrFail($groupId);
        $this->assertOwner($conv);

        $role = GroupRole::where('id', $roleId)->where('conversation_id', $groupId)->firstOrFail();
        abort_unless($conv->participants()->where('user_id', $userId)->exists(), 404, 'User not in group');

        GroupMemberRole::firstOrCreate([
            'conversation_id' => $groupId,
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);

        return response()->json(['message' => 'Role assigned']);
    }

    public function revoke($groupId, $roleId, $userId)
    {
        $conv = $this->getGroupOrFail($groupId);
        $this->assertOwner($conv);

        GroupMemberRole::where([
            'conversation_id' => $groupId,
            'user_id' => $userId,
            'role_id' => $roleId,
        ])->delete();

        return response()->json(['message' => 'Role revoked']);
    }
}
