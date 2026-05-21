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
        $validated = $request->validate([
            'username' => 'required|string|exists:users,username',
        ]);

        $userId = Auth::id();
        $friend = User::where('username', trim($validated['username']))->firstOrFail();

        if ($friend->id === $userId) {
            return response()->json(['message' => 'You cannot send a friend request to yourself'], 400);
        }

        $existing = Friendship::where(function ($query) use ($friend, $userId) {
            $query->where('user_id', $userId)->where('friend_id', $friend->id);
        })->orWhere(function ($query) use ($friend, $userId) {
            $query->where('user_id', $friend->id)->where('friend_id', $userId);
        })->exists();

        if ($existing) {
            return response()->json(['message' => 'Friend request already exists or you are already friends'], 400);
        }

        $friendship = Friendship::create([
            'user_id' => $userId,
            'friend_id' => $friend->id,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Friend request sent successfully',
            'friendship' => $friendship->load(['user', 'friend']),
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
            'friendship' => $friendship->load(['user', 'friend']),
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

        $friendships = Friendship::where('status', 'accepted')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)->orWhere('friend_id', $userId);
            })
            ->with(['user', 'friend'])
            ->get();

        $friends = $friendships->map(function ($friendship) use ($userId) {
            $friend = $friendship->user_id === $userId ? $friendship->friend : $friendship->user;

            return [
                'id' => $friend->id,
                'name' => $friend->name,
                'username' => $friend->username,
                'email' => $friend->email,
            ];
        })->values();

        return response()->json(['friends' => $friends]);
    }

    public function listPending()
    {
        $userId = Auth::id();

        $received = Friendship::where('friend_id', $userId)
            ->where('status', 'pending')
            ->with(['user', 'friend'])
            ->get();

        $sent = Friendship::where('user_id', $userId)
            ->where('status', 'pending')
            ->with(['user', 'friend'])
            ->get();

        return response()->json([
            'received' => $received,
            'sent' => $sent,
        ]);
    }

    public function removeFriend($friendId)
    {
        $userId = Auth::id();

        $friendship = Friendship::where('status', 'accepted')
            ->where(function ($query) use ($userId, $friendId) {
                $query->where(function ($q) use ($userId, $friendId) {
                    $q->where('user_id', $userId)->where('friend_id', $friendId);
                })->orWhere(function ($q) use ($userId, $friendId) {
                    $q->where('user_id', $friendId)->where('friend_id', $userId);
                });
            })
            ->first();

        if (!$friendship) {
            return response()->json(['message' => 'Friendship not found'], 404);
        }

        $friendship->delete();

        return response()->json(['message' => 'Friend removed successfully']);
    }

    public function listUsers(Request $request)
    {
        $users = User::query()
            ->where('id', '!=', Auth::id())
            ->when($request->filled('query'), function ($query) use ($request) {
                $term = '%' . $request->string('query')->toString() . '%';
                $query->where(function ($builder) use ($term) {
                    $builder->where('name', 'like', $term)
                        ->orWhere('username', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'username', 'email']);

        return response()->json(['users' => $users]);
    }

    public function searchUsers(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1|max:50',
        ]);

        $searchTerm = '%' . trim($request->string('query')->toString()) . '%';
        
        $users = User::where('username', 'like', $searchTerm)
            ->where('id', '!=', Auth::id())
            ->limit(10)
            ->get(['id', 'name', 'username']);

        return response()->json(['users' => $users]);
    }
}
