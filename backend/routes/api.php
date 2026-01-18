<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DrawingController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\FriendshipController;
use App\Http\Controllers\Api\ConversationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes - Drawings (viewing only)
Route::get('/drawings', [DrawingController::class, 'index']);
Route::get('/drawings/{id}', [DrawingController::class, 'show']);

// Public stats endpoint
Route::get('/stats', function () {
    return response()->json([
        'success' => true,
        'data' => [
            'total_users' => \App\Models\User::count(),
            'total_artworks' => \App\Models\Drawing::count(),
            'online_users' => \App\Models\User::where('updated_at', '>=', now()->subMinutes(5))->count(),
        ]
    ]);
});

// Public routes - Voting (requires auth)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/drawings/{id}/vote', [DrawingController::class, 'vote']);
    Route::delete('/drawings/{id}/vote', [DrawingController::class, 'unvote']);
    Route::get('/drawings/{id}/check-vote', [DrawingController::class, 'checkVote']);
});

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (authenticated users only)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/drawings', [DrawingController::class, 'store']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::delete('/drawings/{id}', [DrawingController::class, 'destroy']);
    
    // Friend request routes
    Route::post('/friends/request', [FriendshipController::class, 'sendRequest']);
    Route::post('/friends/{id}/accept', [FriendshipController::class, 'acceptRequest']);
    Route::delete('/friends/{id}/reject', [FriendshipController::class, 'rejectRequest']);
    Route::get('/friends', [FriendshipController::class, 'listFriends']);
    Route::get('/friends/pending', [FriendshipController::class, 'listPending']);
    Route::delete('/friends/{id}', [FriendshipController::class, 'removeFriend']);
    Route::get('/users/search', [FriendshipController::class, 'searchUsers']);
    
    // Conversation routes
    Route::post('/conversations', [ConversationController::class, 'getOrCreate']);
    Route::get('/conversations', [ConversationController::class, 'listConversations']);
    Route::get('/conversations/{id}/messages', [ConversationController::class, 'getMessages']);
    
    // Message routes (conversation-based)
    Route::post('/conversations/{id}/messages', [MessageController::class, 'store']);
    Route::get('/conversations/{id}/messages/new', [MessageController::class, 'getNew']);
    Route::delete('/messages/{id}', [MessageController::class, 'destroy']);
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'DoodleVerse API is running',
        'timestamp' => now()
    ]);
});
