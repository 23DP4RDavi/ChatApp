<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GlobalChatController extends Controller
{
    /**
     * GET /messages
     * Return the most recent global chat messages (conversation_id IS NULL).
     * Publicly readable — authentication not required for reading.
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 50), 200);

        $messages = Message::with('user')
            ->whereNull('conversation_id')
            ->orderBy('created_at', 'desc')
            ->limit($perPage)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $messages,
        ]);
    }

    /**
     * GET /messages/new?last_id={id}
     * Poll for new global chat messages with id > last_id.
     * Publicly accessible.
     */
    public function getNew(Request $request)
    {
        $lastId = (int) $request->query('last_id', 0);

        $messages = Message::with('user')
            ->whereNull('conversation_id')
            ->where('id', '>', $lastId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $messages,
        ]);
    }

    /**
     * POST /messages
     * Send a message to the global chat room. Requires authentication.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'content'      => 'nullable|string|max:5000',
            'type'         => 'sometimes|string|in:text,drawing',
            'drawing_data' => 'nullable|array',
        ]);

        if (empty($validated['content']) && empty($validated['drawing_data'])) {
            return response()->json([
                'success' => false,
                'message' => 'Message must have either content or drawing data.',
            ], 422);
        }

        $message = Message::create([
            'user_id'         => Auth::id(),
            'conversation_id' => null,
            'content'         => isset($validated['content']) ? trim($validated['content']) : null,
            'drawing_data'    => $validated['drawing_data'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data'    => $message->load('user'),
        ], 201);
    }
}
