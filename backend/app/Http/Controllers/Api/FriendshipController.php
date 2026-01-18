<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FriendshipController extends Controller
{
    public function sendRequest(Request $request)
    {
        $request->validate([
            'username' => 'required|string|exists:users,username'
        ]);

        $friend = User::where('username', $request->username)->first();

        if ($friend->id === Auth::id()) {
            return response()->json(['message' => 'You cannot send a friend request to yourself'], 400);
        }

        // Check if friendship already exists
        $existing = Friendship::where(function($query) use ($friend) {
            $query->where('user_id', Auth::id())
                  ->where('friend_id', $friend->id);
        })->orWhere(function($query) use ($friend) {
            $query->where('user_id', $friend->id)
                  ->where('friend_id', Auth::id());
        })->first();

        if ($existing) {
            return response()->json(['message' => 'Friend request already exists or you are already friends'], 400);
        }

        $friendship = Friendship::create([
            'user_id' => Auth::id(),
            'friend_id' => $friend->id,
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Friend request sent successfully',
            'friendship' => $friendship->load(['user', 'friend'])
        ], 201);
    }

    public function acceptRequest($friendshipId)
    {
        $friendship = Friendship::findOrFail($friendshipId);

        if ($friendship->friend_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $friendship->update(['status' => 'accepted']);

        return response()->json([
            'message' => 'Friend request accepted',
            'friendship' => $friendship->load(['user', 'friend'])
        ]);
    }

    public function rejectRequest($friendshipId)
    {
        $friendship = Friendship::findOrFail($friendshipId);

        if ($friendship->friend_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $friendship->delete();

        return response()->json(['message' => 'Friend request rejected']);
    }

    public function listFriends()
    {
        $userId = Auth::id();

        $friends = Friendship::where('status', 'accepted')
            ->where(function($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhere('friend_id', $userId);
            })
            ->with(['user', 'friend'])
            ->get()
            ->map(function($friendship) use ($userId) {
                return $friendship->user_id === $userId ? $friendship->friend : $friendship->user;
            });

        return response()->json(['friends' => $friends]);
    }

    public function listPending()
    {
        $received = Friendship::where('friend_id', Auth::id())
            ->where('status', 'pending')
            ->with(['user', 'friend'])
            ->get();

        $sent = Friendship::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->with(['user', 'friend'])
            ->get();

        return response()->json([
            'received' => $received,
            'sent' => $sent
        ]);
    }

    public function removeFriend($friendshipId)
    {
        $friendship = Friendship::findOrFail($friendshipId);
        $userId = Auth::id();

        if ($friendship->user_id !== $userId && $friendship->friend_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $friendship->delete();

        return response()->json(['message' => 'Friend removed successfully']);
    }

    public function searchUsers(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1'
        ]);

        $users = User::where('username', 'like', '%' . $request->query . '%')
            ->where('id', '!=', Auth::id())
            ->limit(10)
            ->get(['id', 'name', 'username']);

        return response()->json(['users' => $users]);
    }
}
