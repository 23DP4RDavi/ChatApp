<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Friendship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    public function getOrCreate(Request $request)
    {
        $request->validate([
            'friend_id' => 'required|exists:users,id'
        ]);

        $userId = Auth::id();
        $friendId = $request->friend_id;

        // Check if they are friends
        $areFriends = Friendship::where('status', 'accepted')
            ->where(function($query) use ($userId, $friendId) {
                $query->where(function($q) use ($userId, $friendId) {
                    $q->where('user_id', $userId)->where('friend_id', $friendId);
                })->orWhere(function($q) use ($userId, $friendId) {
                    $q->where('user_id', $friendId)->where('friend_id', $userId);
                });
            })
            ->exists();

        if (!$areFriends) {
            return response()->json(['message' => 'You can only message friends'], 403);
        }

        // Find existing conversation between these two users
        $conversation = Conversation::whereHas('participants', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereHas('participants', function($query) use ($friendId) {
                $query->where('user_id', $friendId);
            })
            ->where('type', 'direct')
            ->first();

        // Create new conversation if doesn't exist
        if (!$conversation) {
            $conversation = DB::transaction(function() use ($userId, $friendId) {
                $conversation = Conversation::create(['type' => 'direct']);
                
                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $userId
                ]);
                
                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $friendId
                ]);

                return $conversation;
            });
        }

        return response()->json([
            'conversation' => $conversation->load(['users', 'messages.user'])
        ]);
    }

    public function listConversations()
    {
        $userId = Auth::id();

        $conversations = Conversation::whereHas('participants', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['users', 'latestMessage.user'])
            ->get()
            ->map(function($conversation) use ($userId) {
                // Get the other participant
                $otherUser = $conversation->users->firstWhere('id', '!=', $userId);
                
                return [
                    'id' => $conversation->id,
                    'other_user' => $otherUser,
                    'latest_message' => $conversation->latestMessage,
                    'updated_at' => $conversation->updated_at
                ];
            })
            ->sortByDesc('updated_at')
            ->values();

        return response()->json(['conversations' => $conversations]);
    }

    public function getMessages($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Verify user is a participant
        $isParticipant = $conversation->participants()
            ->where('user_id', Auth::id())
            ->exists();

        if (!$isParticipant) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messages = $conversation->messages()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['messages' => $messages]);
    }
}
