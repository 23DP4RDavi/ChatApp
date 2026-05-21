<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class MessageController extends Controller
{
    public function getNew(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);
        $userId = Auth::id();

        $isParticipant = $conversation->participants()->where('user_id', $userId)->exists();

        if (!$isParticipant) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $lastId = (int) $request->input('last_id', 0);
        $channelId = $request->input('channel_id');

        $query = Message::with(['user', 'replyTo.user'])
            ->where('conversation_id', $conversationId)
            ->where('id', '>', $lastId)
            ->orderBy('created_at', 'asc');

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        $messages = $query->get();

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    public function store(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);
        $userId = Auth::id();

        $isParticipant = $conversation->participants()->where('user_id', $userId)->exists();

        if (!$isParticipant) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'content' => 'nullable|string|max:5000|min:0',
            'drawing_data' => 'nullable|array',
            'channel_id' => 'nullable|integer|exists:group_channels,id',
            'reply_to_id' => 'nullable|integer|exists:messages,id',
        ]);

        if (empty($validated['content']) && empty($validated['drawing_data'])) {
            return response()->json([
                'success' => false,
                'message' => 'Message must have either content or drawing data',
            ], 422);
        }

        try {
            $payload = [
                'user_id' => $userId,
                'conversation_id' => $conversationId,
                'content' => !empty($validated['content']) ? trim($validated['content']) : null,
                'drawing_data' => $validated['drawing_data'] ?? null,
            ];

            if (Schema::hasColumn('messages', 'channel_id')) {
                $payload['channel_id'] = $validated['channel_id'] ?? null;
            }

            if (Schema::hasColumn('messages', 'reply_to_id')) {
                $payload['reply_to_id'] = $validated['reply_to_id'] ?? null;
            }

            $message = Message::create($payload);
            $conversation->touch();

            try {
                broadcast(new MessageSent($message))->toOthers();
            } catch (\Throwable $e) {
                // Do not fail message send if realtime broadcast has issues.
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $message->load(['user', 'replyTo.user']),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $message = Message::find($id);

        if (!$message) {
            return response()->json(['success' => false, 'message' => 'Message not found'], 404);
        }

        if ($message->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|min:1|max:5000',
        ]);

        $message->content = trim($validated['content']);
        if (Schema::hasColumn('messages', 'edited_at')) {
            $message->edited_at = now();
        }
        $message->save();

        return response()->json([
            'success' => true,
            'data' => $message->load(['user', 'replyTo.user']),
        ]);
    }

    public function react(Request $request, $id)
    {
                if (!Schema::hasColumn('messages', 'reactions')) {
                    return response()->json(['success' => false, 'message' => 'Reactions are not available yet'], 503);
                }

        $message = Message::find($id);

        if (!$message) {
            return response()->json(['success' => false, 'message' => 'Message not found'], 404);
        }

        $validated = $request->validate([
            'emoji' => 'required|string|max:10',
        ]);

        $userId = $request->user()->id;
        $emoji = $validated['emoji'];
        $reactions = $message->reactions ?? [];

        if (!isset($reactions[$emoji])) {
            $reactions[$emoji] = [];
        }

        $key = array_search($userId, $reactions[$emoji]);
        if ($key !== false) {
            array_splice($reactions[$emoji], $key, 1);
            if (empty($reactions[$emoji])) {
                unset($reactions[$emoji]);
            }
        } else {
            $reactions[$emoji][] = $userId;
        }

        $message->reactions = empty($reactions) ? null : $reactions;
        $message->save();

        return response()->json([
            'success' => true,
            'reactions' => $message->reactions,
        ]);
    }

    public function pin(Request $request, $id)
    {
                if (!Schema::hasColumn('messages', 'is_pinned')) {
                    return response()->json(['success' => false, 'message' => 'Pinning is not available yet'], 503);
                }

        $message = Message::find($id);

        if (!$message) {
            return response()->json(['success' => false, 'message' => 'Message not found'], 404);
        }

        $conversation = \App\Models\Conversation::find($message->conversation_id);

        if (!$conversation || $conversation->owner_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Only the server owner can pin messages'], 403);
        }

        $message->is_pinned = !$message->is_pinned;
        $message->save();

        return response()->json([
            'success' => true,
            'is_pinned' => $message->is_pinned,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $message = Message::find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found',
            ], 404);
        }

        if ($message->user_id !== $request->user()->id) {
            // Group owner can also delete
            $conversation = \App\Models\Conversation::find($message->conversation_id);
            if (!$conversation || $conversation->owner_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully',
        ]);
    }
}
