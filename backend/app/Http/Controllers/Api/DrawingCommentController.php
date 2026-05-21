<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Drawing;
use App\Models\DrawingComment;
use Illuminate\Http\Request;

class DrawingCommentController extends Controller
{
    public function index($drawingId)
    {
        $drawing = Drawing::findOrFail($drawingId);

        $comments = DrawingComment::where('drawing_id', $drawing->id)
            ->with('user:id,name,username,avatar_thumbnail')
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return response()->json($comments);
    }

    public function store(Request $request, $drawingId)
    {
        $drawing = Drawing::findOrFail($drawingId);

        $validated = $request->validate([
            'content' => 'required|string|max:1000|min:1',
        ]);

        $comment = DrawingComment::create([
            'drawing_id' => $drawing->id,
            'user_id'    => $request->user()->id,
            'content'    => trim($validated['content']),
        ]);

        return response()->json(
            $comment->load('user:id,name,username,avatar_thumbnail'),
            201
        );
    }

    public function destroy(Request $request, $id)
    {
        $comment = DrawingComment::findOrFail($id);

        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted']);
    }
}
