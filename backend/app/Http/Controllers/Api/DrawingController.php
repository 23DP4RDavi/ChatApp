<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Drawing;
use App\Models\Vote;
use App\Models\WeeklyTheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DrawingController extends Controller
{
    private function hasDrawingColumn(string $column): bool
    {
        try {
            return Schema::hasTable('drawings') && Schema::hasColumn('drawings', $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function index(Request $request)
    {
        if (!Schema::hasTable('drawings')) {
            return response()->json(['message' => 'Gallery is not available yet.'], 503);
        }

        $request->validate([
            'sort'    => 'sometimes|in:recent,popular',
            'week'    => 'sometimes|in:current',
            'section' => 'sometimes|in:all,theme,free,mine,week',
            'period'  => 'sometimes|in:week,month,3months,6months,year,all',
            'search'  => 'sometimes|string|max:200',
            'tag'     => 'sometimes|string|max:100',
        ]);

        $sort    = $request->input('sort', 'recent');
        $section = $request->input('section', 'all');
        $period  = $request->input('period', 'all');
        $search  = $request->input('search', '');
        $tag     = $request->input('tag', '');

        $hasIsFree = $this->hasDrawingColumn('is_free');
        $hasDescription = $this->hasDrawingColumn('description');
        $hasThemeId = $this->hasDrawingColumn('theme_id') && Schema::hasTable('weekly_themes');
        $hasVotesTable = Schema::hasTable('votes');

        $with = ['user'];
        if ($hasThemeId) {
            $with[] = 'theme';
        }

        $query = Drawing::with($with);
        if ($hasVotesTable) {
            $query->withCount('votes');
        }

        // Section filter
        if ($section === 'free' && $hasIsFree) {
            $query->where('is_free', true);
        } elseif ($section === 'theme' && $hasIsFree) {
            $query->where('is_free', false);
        } elseif ($section === 'week') {
            if ($hasIsFree) {
                $query->where('is_free', false);
            }
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($section === 'mine' && $request->user()) {
            $query->where('user_id', $request->user()->id);
        }

        // Legacy week param support
        if ($request->input('week') === 'current' && $section === 'all') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        }

        // Legacy my param support
        if ($request->boolean('my') && $request->user() && $section === 'all') {
            $query->where('user_id', $request->user()->id);
        }

        // Time period filter (for popularity ranking)
        if ($period !== 'all') {
            $from = match ($period) {
                'week'    => now()->subWeek(),
                'month'   => now()->subMonth(),
                '3months' => now()->subMonths(3),
                '6months' => now()->subMonths(6),
                'year'    => now()->subYear(),
                default   => null,
            };
            if ($from) {
                $query->where('created_at', '>=', $from);
            }
        }

        // Full-text search: title, description, hashtags
        if ($search !== '') {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term);
                if ($this->hasDrawingColumn('description')) {
                    $q->orWhere('description', 'like', $term);
                }
            });
        }

        // Hashtag search
        if ($tag !== '') {
            $tagTerm = '%#' . ltrim($tag, '#') . '%';
            $query->where(function ($q) use ($tagTerm) {
                if ($this->hasDrawingColumn('description')) {
                    $q->where('description', 'like', $tagTerm)
                        ->orWhere('title', 'like', $tagTerm);
                } else {
                    $q->where('title', 'like', $tagTerm);
                }
            });
        }

        if ($sort === 'popular') {
            $query->orderBy('votes_count', 'desc')->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return response()->json($query->paginate(24));
    }

    public function store(Request $request)
    {
        if (!Schema::hasTable('drawings')) {
            return response()->json(['message' => 'Gallery is not available yet.'], 503);
        }

        $validated = $request->validate([
            'title'        => 'required|string|max:255|min:1',
            'description'  => 'nullable|string|max:1000',
            'drawing_data' => 'required|string',
            'thumbnail'    => 'nullable|string',
            'tag_theme'    => 'sometimes|boolean',
            'is_free'      => 'sometimes|boolean',
        ]);

        $hasIsFree = $this->hasDrawingColumn('is_free');
        $hasDescription = $this->hasDrawingColumn('description');
        $hasThemeId = $this->hasDrawingColumn('theme_id') && Schema::hasTable('weekly_themes');

        $isFree = $hasIsFree ? $request->boolean('is_free') : false;

        $themeId = null;
        if ($hasThemeId && !$isFree && $request->boolean('tag_theme')) {
            $theme = WeeklyTheme::where('starts_at', '<=', now())
                ->where('ends_at', '>=', now())
                ->first();
            $themeId = $theme?->id;
        }

        try {
            $payload = [
            'user_id'      => $request->user()->id,
            'title'        => trim($validated['title']),
            'drawing_data' => $validated['drawing_data'],
            'thumbnail'    => !empty($validated['thumbnail']) ? trim($validated['thumbnail']) : null,
            'votes_count'  => 0,
            ];

            if ($hasDescription) {
                $payload['description'] = isset($validated['description']) ? trim($validated['description']) : null;
            }

            if ($hasThemeId) {
                $payload['theme_id'] = $themeId;
            }

            if ($hasIsFree) {
                $payload['is_free'] = $isFree;
            }

            $drawing = Drawing::create($payload);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to save drawing. Please try again.'], 500);
        }

        $with = ['user'];
        if ($hasThemeId) {
            $with[] = 'theme';
        }

        return response()->json([
            'message' => 'Drawing saved successfully',
            'data' => $drawing->load($with),
        ], 201);
    }

    public function show($id)
    {
        if (!Schema::hasTable('drawings')) {
            return response()->json(['message' => 'Gallery is not available yet.'], 503);
        }

        $query = Drawing::with('user');
        if (Schema::hasTable('votes')) {
            $query->withCount('votes');
        }

        $drawing = $query->findOrFail($id);

        return response()->json($drawing);
    }

    public function vote(Request $request, $id)
    {
        if (!Schema::hasTable('votes')) {
            return response()->json(['message' => 'Voting is not available yet.'], 503);
        }

        $drawing = Drawing::findOrFail($id);
        $voterIdentifier = 'user_' . $request->user()->id;

        $alreadyVoted = Vote::where('drawing_id', $id)
            ->where('voter_identifier', $voterIdentifier)
            ->exists();

        if ($alreadyVoted) {
            return response()->json(['message' => 'You have already voted for this drawing'], 400);
        }

        DB::transaction(function () use ($drawing, $voterIdentifier) {
            Vote::create([
                'drawing_id' => $drawing->id,
                'voter_identifier' => $voterIdentifier,
            ]);

            $drawing->increment('votes_count');
        });

        return response()->json([
            'message' => 'Vote recorded successfully',
            'votes_count' => $drawing->fresh()->votes_count,
        ]);
    }

    public function unvote(Request $request, $id)
    {
        if (!Schema::hasTable('votes')) {
            return response()->json(['message' => 'Voting is not available yet.'], 503);
        }

        $drawing = Drawing::findOrFail($id);
        $voterIdentifier = 'user_' . $request->user()->id;

        $vote = Vote::where('drawing_id', $id)
            ->where('voter_identifier', $voterIdentifier)
            ->first();

        if (!$vote) {
            return response()->json(['message' => 'You have not voted for this drawing'], 400);
        }

        DB::transaction(function () use ($drawing, $vote) {
            $vote->delete();
            $drawing->decrement('votes_count');
        });

        return response()->json([
            'message' => 'Vote removed successfully',
            'votes_count' => $drawing->fresh()->votes_count,
        ]);
    }

    public function checkVote(Request $request, $id)
    {
        if (!Schema::hasTable('votes')) {
            return response()->json(['has_voted' => false]);
        }

        $voterIdentifier = 'user_' . $request->user()->id;

        $hasVoted = Vote::where('drawing_id', $id)
            ->where('voter_identifier', $voterIdentifier)
            ->exists();

        return response()->json(['has_voted' => $hasVoted]);
    }

    public function destroy(Request $request, $id)
    {
        $drawing = Drawing::findOrFail($id);

        if ($drawing->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $drawing->delete();

        return response()->json(['message' => 'Drawing deleted successfully']);
    }
}
