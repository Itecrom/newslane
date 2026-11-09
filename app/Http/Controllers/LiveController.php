<?php

namespace App\Http\Controllers;

use App\Models\LiveEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveController extends Controller
{
    public function index(): View
    {
        $events = LiveEvent::where('status', 'live')->latest('started_at')->get();

        return view('live', compact('events'));
    }

    public function show(LiveEvent $event): View
    {
        abort_unless($event->status === 'live', 404);
        $updates = $event->updates()->with('author')->latest()->take(50)->get()->reverse()->values();
        $event->setRelation('updates', $updates);

        return view('live-event', compact('event'));
    }

    public function updates(Request $request, LiveEvent $event): JsonResponse
    {
        abort_unless($event->status === 'live', 404);
        $data = $request->validate(['after' => ['nullable', 'integer', 'min:0']]);
        $updates = $event->updates()
            ->where('id', '>', $data['after'] ?? 0)
            ->oldest('created_at')
            ->with('author:id,name')
            ->take(100)
            ->get()
            ->map(fn ($update) => [
                'id' => $update->id,
                'body' => $update->body,
                'created_at' => $update->created_at->toIso8601String(),
                'time' => $update->created_at->format('H:i'),
                'date' => $update->created_at->format('d M Y'),
                'author' => $update->author->name,
            ]);

        return response()->json(['updates' => $updates])
            ->header('Cache-Control', 'no-store, private');
    }
}
