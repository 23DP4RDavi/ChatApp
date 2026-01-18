<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Drawing;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DrawingController extends Controller
{
    /**
     * Display a listing of drawings (sorted by votes or date)
     */
    public function index(Request $request)
    {
        $sortBy = $request->query('sort', 'recent'); // 'recent' or 'popular'
        
        $query = Drawing::with('user')
            ->withCount('votes');

        if ($sortBy === 'popular') {
            $query->orderBy('votes_count', 'desc')
                  ->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $drawings = $query->paginate(20);

        return response()->json($drawings);
    }

    /**
     * Store a newly created drawing (authenticated users only)
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'drawing_data' => 'required|string',
            'thumbnail' => 'nullable|string',
        ]);

        $drawing = Drawing::create([
            'user_id' => $request->user()->id,
            'guest_name' => null,
            'title' => $request->title,
            'drawing_data' => $request->drawing_data,
            'thumbnail' => $request->thumbnail,
            'votes_count' => 0,
        ]);

        return response()->json([
            'message' => 'Drawing saved successfully',
            'data' => $drawing->load('user')
        ], 201);
    }

    /**
     * Display the specified drawing
     */
    public function show($id)
    {
        $drawing = Drawing::with('user')
            ->withCount('votes')
            ->findOrFail($id);

        return response()->json($drawing);
    }

    /**
     * Vote for a drawing (authenticated users only)
     */
    public function vote(Request $request, $id)
    {
        $drawing = Drawing::findOrFail($id);
        
        // Get voter identifier (user ID for authenticated users)
        $voterIdentifier = 'user_' . $request->user()->id;

        // Check if already voted
        $existingVote = Vote::where('drawing_id', $id)
            ->where('voter_identifier', $voterIdentifier)
            ->first();

        if ($existingVote) {
            return response()->json([
                'message' => 'You have already voted for this drawing'
            ], 400);
        }

        // Create vote
        DB::transaction(function () use ($drawing, $voterIdentifier) {
            Vote::create([
                'drawing_id' => $drawing->id,
                'voter_identifier' => $voterIdentifier,
            ]);

            $drawing->increment('votes_count');
        });

        return response()->json([
            'message' => 'Vote recorded successfully',
            'votes_count' => $drawing->fresh()->votes_count
        ]);
    }

    /**
     * Remove vote from a drawing (authenticated users only)
     */
    public function unvote(Request $request, $id)
    {
        $drawing = Drawing::findOrFail($id);
        $voterIdentifier = 'user_' . $request->user()->id;

        $vote = Vote::where('drawing_id', $id)
            ->where('voter_identifier', $voterIdentifier)
            ->first();

        if (!$vote) {
            return response()->json([
                'message' => 'You have not voted for this drawing'
            ], 400);
        }

        DB::transaction(function () use ($drawing, $vote) {
            $vote->delete();
            $drawing->decrement('votes_count');
        });

        return response()->json([
            'message' => 'Vote removed successfully',
            'votes_count' => $drawing->fresh()->votes_count
        ]);
    }

    /**
     * Check if user has voted for a drawing (authenticated users only)
     */
    public function checkVote(Request $request, $id)
    {
        $voterIdentifier = 'user_' . $request->user()->id;
        
        $hasVoted = Vote::where('drawing_id', $id)
            ->where('voter_identifier', $voterIdentifier)
            ->exists();

        return response()->json([
            'has_voted' => $hasVoted
        ]);
    }

    /**
     * Remove the specified drawing
     */
    public function destroy(Request $request, $id)
    {
        $drawing = Drawing::findOrFail($id);

        // Allow deletion if user owns it or if it's a guest drawing (optional: add admin check)
        if ($request->user() && $drawing->user_id === $request->user()->id) {
            $drawing->delete();
            return response()->json([
                'message' => 'Drawing deleted successfully'
            ]);
        }

        return response()->json([
            'message' => 'Unauthorized'
        ], 403);
    }
}
