<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventStreamViewer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventStreamController extends Controller
{
    public function heartbeat(Request $request, Event $event): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        if (! $event->live_stream_url) {
            return response()->json([
                'ok' => false,
                'message' => 'Live stream is not active.',
            ], 422);
        }

        EventStreamViewer::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'user_id' => $user->id,
            ],
            [
                'last_seen_at' => now(),
            ]
        );

        return response()->json([
            'ok' => true,
            'viewer_count' => $event->activeStreamViewerCount(),
        ]);
    }
}
