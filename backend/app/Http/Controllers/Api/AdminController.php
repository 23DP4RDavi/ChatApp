<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Drawing;
use App\Models\DrawingComment;
use App\Models\Message;
use App\Models\User;
use App\Models\WeeklyTheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    // ── Guard: only admins can call any action here ──────────────
    protected function assertAdmin(Request $request)
    {
        if (!$request->user() || !$request->user()->is_admin) {
            abort(403, 'Forbidden');
        }
    }

    // ════════════════════════════════════════════════════════════
    // STATS
    // ════════════════════════════════════════════════════════════
    public function stats(Request $request)
    {
        $this->assertAdmin($request);
        return response()->json([
            'users'         => User::count(),
            'messages'      => Message::count(),
            'drawings'      => Drawing::count(),
            'conversations' => Conversation::count(),
            'comments'      => DrawingComment::count(),
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // USERS
    // ════════════════════════════════════════════════════════════
    public function listUsers(Request $request)
    {
        $this->assertAdmin($request);
        $q = $request->query('q', '');
        $query = User::withCount(['drawings', 'messages'])
            ->orderBy('created_at', 'desc');
        if ($q) {
            $query->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('username', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
            });
        }
        return response()->json($query->paginate(20));
    }

    public function updateUser(Request $request, $id)
    {
        $this->assertAdmin($request);
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'username' => ['sometimes','string','max:60', Rule::unique('users')->ignore($id)],
            'email'    => ['sometimes','email', Rule::unique('users')->ignore($id)],
            'is_admin' => 'sometimes|boolean',
            'password' => 'sometimes|string|min:8',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        return response()->json($user);
    }

    public function deleteUser(Request $request, $id)
    {
        $this->assertAdmin($request);
        // Prevent self-deletion
        if ($request->user()->id == $id) {
            return response()->json(['message' => 'Cannot delete your own account.'], 422);
        }
        User::findOrFail($id)->delete();
        return response()->json(['message' => 'User deleted.']);
    }

    // ════════════════════════════════════════════════════════════
    // MESSAGES  (global chat — no conversation_id)
    // ════════════════════════════════════════════════════════════
    public function listMessages(Request $request)
    {
        $this->assertAdmin($request);
        $q = $request->query('q', '');
        $query = Message::with('user')
            ->orderBy('created_at', 'desc');
        if ($q) {
            $query->where('content', 'like', "%{$q}%");
        }
        return response()->json($query->paginate(30));
    }

    public function deleteMessage(Request $request, $id)
    {
        $this->assertAdmin($request);
        Message::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    // ════════════════════════════════════════════════════════════
    // DRAWINGS
    // ════════════════════════════════════════════════════════════
    public function listDrawings(Request $request)
    {
        $this->assertAdmin($request);
        $q = $request->query('q', '');
        $query = Drawing::with('user')
            ->orderBy('created_at', 'desc');
        if ($q) {
            $query->where('title', 'like', "%{$q}%");
        }
        return response()->json($query->paginate(20));
    }

    public function updateDrawing(Request $request, $id)
    {
        $this->assertAdmin($request);
        $drawing = Drawing::findOrFail($id);
        $data = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'is_free'     => 'sometimes|boolean',
        ]);
        $drawing->update($data);
        return response()->json($drawing);
    }

    public function deleteDrawing(Request $request, $id)
    {
        $this->assertAdmin($request);
        Drawing::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    // ════════════════════════════════════════════════════════════
    // CONVERSATIONS / GROUPS
    // ════════════════════════════════════════════════════════════
    public function listConversations(Request $request)
    {
        $this->assertAdmin($request);
        return response()->json(
            Conversation::withCount('participants')
                ->orderBy('created_at', 'desc')
                ->paginate(20)
        );
    }

    public function deleteConversation(Request $request, $id)
    {
        $this->assertAdmin($request);
        Conversation::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    // ════════════════════════════════════════════════════════════
    // WEEKLY THEMES
    // ════════════════════════════════════════════════════════════
    public function listThemes(Request $request)
    {
        $this->assertAdmin($request);
        return response()->json(WeeklyTheme::orderBy('year', 'asc')->orderBy('week_number', 'asc')->get());
    }

    public function storeTheme(Request $request)
    {
        $this->assertAdmin($request);
        $data = $request->validate([
            'theme_name'  => 'required|string|max:255',
            'week_number' => 'required|integer|min:1|max:53',
            'year'        => 'required|integer|min:2020',
            'starts_at'   => 'required|date',
            'ends_at'     => 'required|date|after_or_equal:starts_at',
            'description' => 'sometimes|nullable|string|max:500',
            'emoji'       => 'sometimes|nullable|string|max:10',
        ]);
        $theme = WeeklyTheme::create($data);
        return response()->json($theme, 201);
    }

    public function updateTheme(Request $request, $id)
    {
        $this->assertAdmin($request);
        $theme = WeeklyTheme::findOrFail($id);
        $data = $request->validate([
            'theme_name'  => 'sometimes|string|max:255',
            'week_number' => 'sometimes|integer|min:1|max:53',
            'year'        => 'sometimes|integer|min:2020',
            'starts_at'   => 'sometimes|date',
            'ends_at'     => 'sometimes|date',
            'description' => 'sometimes|nullable|string|max:500',
            'emoji'       => 'sometimes|nullable|string|max:10',
        ]);
        $theme->update($data);
        return response()->json($theme);
    }

    public function deleteTheme(Request $request, $id)
    {
        $this->assertAdmin($request);
        WeeklyTheme::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
