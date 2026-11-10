<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LiveEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LiveAdminController extends Controller
{
    public function index(): View
    {
        $events = LiveEvent::withCount('updates')->latest()->paginate(10);

        return view('admin.live.index', compact('events'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:live_events,slug'],
            'summary' => ['required', 'string', 'max:500'],
            'video_embed_url' => ['nullable', 'url:https', 'regex:~\Ahttps://www\.youtube-nocookie\.com/embed/[A-Za-z0-9_-]{11}(?:\?[A-Za-z0-9_=&%-]+)?\z~', 'max:255'],
        ]);
        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['title']);
        if (LiveEvent::where('slug', $data['slug'])->exists()) {
            $data['slug'] .= '-'.Str::lower(Str::random(5));
        }
        $data['status'] = 'live';
        $data['started_at'] = now();
        LiveEvent::create($data);

        return back()->with('status', 'Live coverage started.');
    }

    public function update(LiveEvent $event): RedirectResponse
    {
        $event->update(['status' => 'closed', 'ended_at' => now()]);

        return back()->with('status', 'Live coverage closed.');
    }

    public function addUpdate(Request $request, LiveEvent $event): RedirectResponse
    {
        abort_unless($event->status === 'live', 404);
        $data = $request->validate(['body' => ['required', 'string', 'min:3', 'max:5000']]);
        $event->updates()->create($data + ['user_id' => $request->user()->id]);

        return back()->with('status', 'Live update published.');
    }

    public function destroy(LiveEvent $event): RedirectResponse
    {
        $event->delete();

        return back()->with('status', 'Live coverage deleted.');
    }
}
