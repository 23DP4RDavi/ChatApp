<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\GroupInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GroupInviteController extends Controller
{
    public function create(Request $request, $groupId)
    {
        $conv = Conversation::findOrFail($groupId);
        abort_unless($conv->type === 'group', 404);
        abort_unless($conv->participants()->where('user_id', Auth::id())->exists(), 403, 'Not a member');

        $data = $request->validate([
            'expires_in_hours' => 'sometimes|integer|min:1|max:168', // max 7 days
            'max_uses' => 'sometimes|integer|min:1|max:1000',
        ]);

        $token = Str::random(10);
        $expiresAt = isset($data['expires_in_hours'])
            ? now()->addHours($data['expires_in_hours'])
            : null;

        $invite = GroupInvite::create([
            'conversation_id' => $groupId,
            'created_by' => Auth::id(),
            'token' => $token,
            'expires_at' => $expiresAt,
            'max_uses' => $data['max_uses'] ?? null,
            'uses' => 0,
        ]);

        return response()->json([
            'invite' => $invite,
            'token' => $token,
            'url' => config('app.frontend_url', 'http://localhost:5173') . '/invite/' . $token,
        ], 201);
    }

    public function show($token)
    {
        $invite = GroupInvite::where('token', $token)->with('conversation')->firstOrFail();

        if ($invite->isExpired() || $invite->isFull()) {
            return response()->json(['message' => 'This invite is no longer valid'], 410);
        }

        $memberCount = $invite->conversation->participants()->count();

        return response()->json([
            'group' => [
                'id' => $invite->conversation->id,
                'name' => $invite->conversation->name,
                'member_count' => $memberCount,
            ],
            'expires_at' => $invite->expires_at,
            'uses' => $invite->uses,
            'max_uses' => $invite->max_uses,
        ]);
    }

    public function join(Request $request, $token)
    {
        $invite = GroupInvite::where('token', $token)->with('conversation')->firstOrFail();

        if ($invite->isExpired()) {
            return response()->json(['message' => 'This invite has expired'], 410);
        }
        if ($invite->isFull()) {
            return response()->json(['message' => 'This invite has reached its maximum uses'], 410);
        }

        $conv = $invite->conversation;
        $userId = Auth::id();

        // Already a member?
        if ($conv->participants()->where('user_id', $userId)->exists()) {
            return response()->json([
                'message' => 'Already a member',
                'conversation_id' => $conv->id,
            ]);
        }

        $conv->participants()->create(['user_id' => $userId]);
        $invite->increment('uses');

        return response()->json([
            'message' => 'Joined successfully',
            'conversation_id' => $conv->id,
        ]);
    }
}
