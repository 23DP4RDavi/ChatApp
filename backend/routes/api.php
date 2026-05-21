<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\DrawingCommentController;
use App\Http\Controllers\Api\DrawingController;
use App\Http\Controllers\Api\FriendshipController;
use App\Http\Controllers\Api\GlobalChatController;
use App\Http\Controllers\Api\GroupChannelController;
use App\Http\Controllers\Api\GroupInviteController;
use App\Http\Controllers\Api\GroupRoleController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\WeeklyThemeController;
use App\Models\Drawing;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Broadcasting channel authentication for Echo/Reverb
Route::post('/broadcasting/auth', function (\Illuminate\Http\Request $request) {
    return Broadcast::auth($request);
})->middleware('auth:sanctum');

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => 'chatapp-backend',
        'timestamp' => now()->toISOString(),
    ]);
});

Route::get('/stats', function () {
    return response()->json([
        'data' => [
            'total_users' => User::count(),
            'total_artworks' => Drawing::count(),
            'online_users' => User::where('updated_at', '>=', now()->subMinutes(5))->count(),
        ],
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect']);
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback']);

Route::get('/drawings', [DrawingController::class, 'index']);
Route::get('/drawings/{id}', [DrawingController::class, 'show']);
Route::get('/drawings/{id}/comments', [DrawingCommentController::class, 'index']);

// Public profile
Route::get('/profile/{username}', function ($username) {
    $user = User::where('username', $username)->firstOrFail();

    $drawingsCount = Drawing::where('user_id', $user->id)->count();

    $friendsCount = Friendship::where('status', 'accepted')
        ->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)->orWhere('friend_id', $user->id);
        })->count();

    $recentDrawings = Drawing::where('user_id', $user->id)
        ->withCount('votes')
        ->orderBy('created_at', 'desc')
        ->limit(12)
        ->get(['id', 'title', 'thumbnail', 'created_at', 'votes_count']);

    return response()->json([
        'id'              => $user->id,
        'name'            => $user->name,
        'username'        => $user->username,
        'avatar_thumbnail'=> $user->avatar_thumbnail,
        'drawings_count'  => $drawingsCount,
        'friends_count'   => $friendsCount,
        'recent_drawings' => $recentDrawings,
    ]);
});

// Weekly theme (public)
Route::get('/weekly-theme', [WeeklyThemeController::class, 'current']);
Route::get('/weekly-archive', [WeeklyThemeController::class, 'archive']);
Route::get('/weekly-archive/{weekNumber}/{year}', [WeeklyThemeController::class, 'weekDrawings']);

// Public invite info
Route::get('/invites/{token}', [GroupInviteController::class, 'show']);

// ── Global Chat (PictoChat) — public read, auth required for write ──
Route::get('/messages',     [GlobalChatController::class, 'index']);
Route::get('/messages/new', [GlobalChatController::class, 'getNew']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user', [AuthController::class, 'updateUser']);

    // Global chat — send message
    Route::post('/messages', [GlobalChatController::class, 'store']);

    Route::get('/users', [FriendshipController::class, 'listUsers']);
    Route::get('/users/search', [FriendshipController::class, 'searchUsers']);

    Route::post('/drawings', [DrawingController::class, 'store']);
    Route::delete('/drawings/{id}', [DrawingController::class, 'destroy']);
    Route::post('/drawings/{id}/vote', [DrawingController::class, 'vote']);
    Route::delete('/drawings/{id}/vote', [DrawingController::class, 'unvote']);
    Route::get('/drawings/{id}/check-vote', [DrawingController::class, 'checkVote']);
    Route::post('/drawings/{id}/comments', [DrawingCommentController::class, 'store']);
    Route::delete('/comments/{id}', [DrawingCommentController::class, 'destroy']);

    Route::post('/friends/request', [FriendshipController::class, 'sendRequest']);
    Route::post('/friends/{id}/accept', [FriendshipController::class, 'acceptRequest']);
    Route::delete('/friends/{id}/reject', [FriendshipController::class, 'rejectRequest']);
    Route::get('/friends', [FriendshipController::class, 'listFriends']);
    Route::get('/friends/pending', [FriendshipController::class, 'listPending']);
    Route::delete('/friends/{id}', [FriendshipController::class, 'removeFriend']);

    Route::post('/conversations', [ConversationController::class, 'getOrCreate']);
    Route::post('/conversations/group', [ConversationController::class, 'createGroup']);
    Route::get('/conversations', [ConversationController::class, 'listConversations']);
    Route::get('/conversations/{id}/messages', [ConversationController::class, 'getMessages']);

    Route::post('/conversations/{id}/messages', [MessageController::class, 'store']);
    Route::get('/conversations/{id}/messages/new', [MessageController::class, 'getNew']);
    Route::delete('/messages/{id}', [MessageController::class, 'destroy']);
    Route::put('/messages/{id}', [MessageController::class, 'update']);
    Route::post('/messages/{id}/react', [MessageController::class, 'react']);
    Route::patch('/messages/{id}/pin', [MessageController::class, 'pin']);

    Route::get('/notifications', [NotificationController::class, 'index']);

    // Group settings
    Route::put('/groups/{id}', [ConversationController::class, 'updateGroup']);

    // Group channels
    Route::get('/groups/{id}/channels', [GroupChannelController::class, 'index']);
    Route::post('/groups/{id}/channels', [GroupChannelController::class, 'store']);
    Route::post('/groups/{id}/channels/reorder', [GroupChannelController::class, 'reorder']);
    Route::put('/groups/{id}/channels/{channelId}', [GroupChannelController::class, 'update']);
    Route::delete('/groups/{id}/channels/{channelId}', [GroupChannelController::class, 'destroy']);
    Route::get('/groups/{id}/members', [GroupChannelController::class, 'members']);

    // Group roles
    Route::get('/groups/{id}/roles', [GroupRoleController::class, 'index']);
    Route::post('/groups/{id}/roles', [GroupRoleController::class, 'store']);
    Route::put('/groups/{id}/roles/{roleId}', [GroupRoleController::class, 'update']);
    Route::delete('/groups/{id}/roles/{roleId}', [GroupRoleController::class, 'destroy']);
    Route::post('/groups/{id}/roles/{roleId}/assign/{userId}', [GroupRoleController::class, 'assign']);
    Route::delete('/groups/{id}/roles/{roleId}/revoke/{userId}', [GroupRoleController::class, 'revoke']);

    // Group invites
    Route::post('/groups/{id}/invites', [GroupInviteController::class, 'create']);
    Route::post('/invites/{token}/join', [GroupInviteController::class, 'join']);

    // Presence — heartbeat + online user list
    Route::post('/presence/pulse', function () {
        auth()->user()->touch();
        return response()->json(['ok' => true]);
    });
    Route::get('/users/online', function () {
        $ids = \App\Models\User::where('updated_at', '>=', now()->subMinutes(5))->pluck('id');
        return response()->json(['online_ids' => $ids]);
    });

    // ── Admin routes (requires is_admin = true) ──────────────────
    Route::prefix('admin')->group(function () {
        Route::get('/stats',                   [AdminController::class, 'stats']);

        Route::get('/users',                   [AdminController::class, 'listUsers']);
        Route::put('/users/{id}',              [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}',           [AdminController::class, 'deleteUser']);

        Route::get('/messages',                [AdminController::class, 'listMessages']);
        Route::delete('/messages/{id}',        [AdminController::class, 'deleteMessage']);

        Route::get('/drawings',                [AdminController::class, 'listDrawings']);
        Route::put('/drawings/{id}',           [AdminController::class, 'updateDrawing']);
        Route::delete('/drawings/{id}',        [AdminController::class, 'deleteDrawing']);

        Route::get('/conversations',           [AdminController::class, 'listConversations']);
        Route::delete('/conversations/{id}',   [AdminController::class, 'deleteConversation']);

        Route::get('/themes',                  [AdminController::class, 'listThemes']);
        Route::post('/themes',                 [AdminController::class, 'storeTheme']);
        Route::put('/themes/{id}',             [AdminController::class, 'updateTheme']);
        Route::delete('/themes/{id}',          [AdminController::class, 'deleteTheme']);
    });
});
