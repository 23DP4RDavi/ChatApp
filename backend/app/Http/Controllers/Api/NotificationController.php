<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\Message;
use App\Models\User;
use App\Models\Vote;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $since = now()->subDays(7);
        if ($request->filled('since')) {
            try {
                $since = Carbon::parse($request->string('since')->toString());
            } catch (\Throwable $e) {
                $since = now()->subDays(7);
            }
        }

        $likes = Vote::query()
            ->where('created_at', '>', $since)
            ->where('voter_identifier', 'like', 'user_%')
            ->whereHas('drawing', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with('drawing:id,title,user_id')
            ->latest('created_at')
            ->limit(20)
            ->get();

        $voterIds = $likes
            ->map(function (Vote $vote) {
                return (int) str_replace('user_', '', (string) $vote->voter_identifier);
            })
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $votersById = User::query()
            ->whereIn('id', $voterIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $likes = $likes->map(function (Vote $vote) use ($votersById) {
                $voterId = (int) str_replace('user_', '', (string) $vote->voter_identifier);
                $voter = $votersById->get($voterId);

                return [
                    'id' => 'like_' . $vote->id,
                    'type' => 'like',
                    'title' => 'Someone liked your drawing',
                    'text' => ($voter?->name ?? 'Someone') . ' liked "' . ($vote->drawing?->title ?? 'your drawing') . '"',
                    'created_at' => optional($vote->created_at)->toISOString(),
                    'meta' => [
                        'drawing_id' => $vote->drawing?->id,
                        'drawing_title' => $vote->drawing?->title,
                        'actor_name' => $voter?->name,
                    ],
                ];
            });

        $friendRequests = Friendship::query()
            ->where('friend_id', $user->id)
            ->where('status', 'pending')
            ->where('created_at', '>', $since)
            ->with('user:id,name,username')
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(function (Friendship $friendship) {
                return [
                    'id' => 'friend_request_' . $friendship->id,
                    'type' => 'friend_request',
                    'title' => 'New friend request',
                    'text' => ($friendship->user?->name ?? 'Someone') . ' sent you a friend request',
                    'created_at' => optional($friendship->created_at)->toISOString(),
                    'meta' => [
                        'friendship_id' => $friendship->id,
                        'actor_name' => $friendship->user?->name,
                    ],
                ];
            });

        $friendAccepts = Friendship::query()
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->where('updated_at', '>', $since)
            ->with('friend:id,name,username')
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->map(function (Friendship $friendship) {
                return [
                    'id' => 'friend_accept_' . $friendship->id,
                    'type' => 'friend_accept',
                    'title' => 'Friend request accepted',
                    'text' => ($friendship->friend?->name ?? 'Someone') . ' accepted your friend request',
                    'created_at' => optional($friendship->updated_at)->toISOString(),
                    'meta' => [
                        'friendship_id' => $friendship->id,
                        'actor_name' => $friendship->friend?->name,
                    ],
                ];
            });

        $messages = Message::query()
            ->where('created_at', '>', $since)
            ->where('user_id', '!=', $user->id)
            ->whereHas('conversation.participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['user:id,name,username', 'conversation:id'])
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(function (Message $message) {
                return [
                    'id' => 'message_' . $message->id,
                    'type' => 'message',
                    'title' => 'New message',
                    'text' => ($message->user?->name ?? 'Someone') . ': ' . ($message->content ?: 'sent a drawing'),
                    'created_at' => optional($message->created_at)->toISOString(),
                    'meta' => [
                        'conversation_id' => $message->conversation_id,
                        'message_id' => $message->id,
                        'actor_name' => $message->user?->name,
                    ],
                ];
            });

        $notifications = collect()
            ->merge($likes)
            ->merge($friendRequests)
            ->merge($friendAccepts)
            ->merge($messages)
            ->sortByDesc('created_at')
            ->take(30)
            ->values();

        return response()->json([
            'data' => [
                'notifications' => $notifications,
                'counts' => [
                    'likes' => $likes->count(),
                    'friend_requests' => $friendRequests->count(),
                    'friend_accepts' => $friendAccepts->count(),
                    'messages' => $messages->count(),
                    'total_unread' => $notifications->count(),
                ],
            ],
        ]);
    }
}
