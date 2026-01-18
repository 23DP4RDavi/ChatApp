<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Get messages for a specific conversation after a specific ID (for polling)
     */
    public function getNew(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Verify user is a participant
        $isParticipant = $conversation->participants()
            ->where('user_id', Auth::id())
            ->exists();

        if (!$isParticipant) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $lastId = $request->input('last_id', 0);
        
        $messages = Message::with('user')
            ->where('conversation_id', $conversationId)
            ->where('id', '>', $lastId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * Send a new message in a conversation
     */
    public function store(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Verify user is a participant
        $isParticipant = $conversation->participants()
            ->where('user_id', Auth::id())
            ->exists();

        if (!$isParticipant) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string|max:5000',
            'drawing_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Must have either content or drawing_data
        if (!$request->content && !$request->drawing_data) {
            return response()->json([
                'success' => false,
                'message' => 'Message must have either content or drawing data'
            ], 422);
        }

        $message = Message::create([
            'user_id' => Auth::id(),
            'conversation_id' => $conversationId,
            'content' => $request->content,
            'drawing_data' => $request->drawing_data,
        ]);

        $message->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $message
        ], 201);
    }

    /**
     * Delete a message (only owner can delete)
     */
    public function destroy(Request $request, $id)
    {
        $message = Message::find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found'
            ], 404);
        }

        if ($message->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    }
}
