<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Friendship;
use App\Models\GroupChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    public function getOrCreate(Request $request)
    {
        $friendId = $request->validate([
            'friend_id' => 'required|integer|exists:users,id',
        ])['friend_id'];

        $userId = Auth::id();

        if ($userId === $friendId) {
            return response()->json(['message' => 'You cannot create a conversation with yourself'], 400);
        }

        $areFriends = Friendship::where('status', 'accepted')
            ->where(function ($query) use ($userId, $friendId) {
                $query->where(fn ($q) => $q->where('user_id', $userId)->where('friend_id', $friendId))
                    ->orWhere(fn ($q) => $q->where('user_id', $friendId)->where('friend_id', $userId));
            })
            ->exists();

        if (!$areFriends) {
            return response()->json(['message' => 'You can only message friends'], 403);
        }

        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($query) => $query->where('user_id', $userId))
            ->whereHas('participants', fn ($query) => $query->where('user_id', $friendId))
            ->first();

        if (!$conversation) {
            $conversation = DB::transaction(function () use ($userId, $friendId) {
                $created = Conversation::create(['type' => 'direct']);

                ConversationParticipant::create([
                    'conversation_id' => $created->id,
                    'user_id' => $userId,
                ]);

                ConversationParticipant::create([
                    'conversation_id' => $created->id,
                    'user_id' => $friendId,
                ]);

                return $created;
            });
        }

        return response()->json([
            'conversation' => $conversation->load(['users', 'messages.user']),
        ]);
    }

    public function listConversations()
    {
        $userId = Auth::id();
        $conversations = Conversation::whereHas('participants', fn ($query) => $query->where('user_id', $userId))
            ->with(['users', 'latestMessage.user'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($conversation) use ($userId) {
                if ($conversation->type === 'group') {
                    return [
                        'id' => $conversation->id,
                        'type' => 'group',
                        'name' => $conversation->name,
                        'avatar_thumbnail' => $conversation->avatar_thumbnail,
                        'owner_id' => $conversation->owner_id,
                        'participants' => $conversation->users,
                        'latest_message' => $conversation->latestMessage,
                        'updated_at' => $conversation->updated_at,
                    ];
                } else {
                    $otherUser = $conversation->users->firstWhere('id', '!=', $userId);
                    return [
                        'id' => $conversation->id,
                        'type' => 'direct',
                        'other_user' => $otherUser,
                        'latest_message' => $conversation->latestMessage,
                        'updated_at' => $conversation->updated_at,
                    ];
                }
            })
            ->values();
        return response()->json(['conversations' => $conversations]);
    }

    public function getMessages(\Illuminate\Http\Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        $isParticipant = $conversation->participants()->where('user_id', Auth::id())->exists();

        if (!$isParticipant) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = $conversation->messages()->with(['user', 'replyTo.user'])->orderBy('created_at', 'asc');

        if ($request->filled('channel_id')) {
            $query->where('channel_id', $request->integer('channel_id'));
        }

        $messages = $query->get();

        return response()->json(['messages' => $messages]);
    }

    public function createGroup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:80',
            'participant_ids' => 'required|array|min:1|max:30',
            'participant_ids.*' => 'integer|distinct|exists:users,id',
        ]);

        $userId = Auth::id();

        $participantIds = collect($validated['participant_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id !== (int) $userId)
            ->unique()
            ->values();

        if ($participantIds->isEmpty()) {
            return response()->json([
                'message' => 'Group must include at least one other participant',
            ], 422);
        }

        $friendIds = Friendship::where('status', 'accepted')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('friend_id', $userId);
            })
            ->get()
            ->map(function ($friendship) use ($userId) {
                return (int) ($friendship->user_id === $userId ? $friendship->friend_id : $friendship->user_id);
            });

        $nonFriendParticipants = $participantIds->diff($friendIds);
        if ($nonFriendParticipants->isNotEmpty()) {
            return response()->json([
                'message' => 'You can only add friends to a group',
            ], 403);
        }

        $conversation = DB::transaction(function () use ($validated, $userId, $participantIds) {
            $created = Conversation::create([
                'type' => 'group',
                'name' => trim($validated['name']),
                'owner_id' => $userId,
            ]);

            ConversationParticipant::create([
                'conversation_id' => $created->id,
                'user_id' => $userId,
            ]);

            foreach ($participantIds as $participantId) {
                ConversationParticipant::create([
                    'conversation_id' => $created->id,
                    'user_id' => $participantId,
                ]);
            }

            // Create default #general channel
            GroupChannel::create([
                'conversation_id' => $created->id,
                'name' => 'general',
                'type' => 'text',
                'position' => 0,
            ]);

            return $created;
        });

        return response()->json([
            'conversation' => $conversation->load(['users', 'latestMessage.user']),
        ], 201);
    }

    public function updateGroup(Request $request, $id)
    {
        $conversation = Conversation::findOrFail($id);

        if ($conversation->type !== 'group') {
            return response()->json(['message' => 'Not a group conversation'], 400);
        }

        if ((int) $conversation->owner_id !== (int) Auth::id()) {
            return response()->json(['message' => 'Only the group owner can edit this server'], 403);
        }

        $validated = $request->validate([
            'name'             => 'sometimes|string|min:2|max:80',
            'avatar_thumbnail' => 'sometimes|nullable|string|max:524288',
        ]);

        $conversation->update($validated);

        return response()->json(['conversation' => $conversation]);
    }
}
