<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AdvertisementAdminController extends Controller
{
    public function index(): View
    {
        $advertisements = Advertisement::latest()->paginate(15);

        return view('admin.ads.index', compact('advertisements'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'target_url' => ['required', 'url:http,https', 'max:2048'],
            'placement' => ['required', 'in:homepage,article'],
            'image' => ['required', 'image', 'max:5120'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
        $imagePath = Storage::disk('public')->putFile('ads', $request->file('image'));

        if (! is_string($imagePath)) {
            throw new RuntimeException('Unable to store the advertisement image.');
        }

        $data['image_path'] = $imagePath;
        $data['active'] = $request->boolean('active');
        Advertisement::create($data);

        return back()->with('status', 'Advertisement added.');
    }

    public function update(Request $request, Advertisement $advertisement): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'target_url' => ['required', 'url:http,https', 'max:2048'],
            'placement' => ['required', 'in:homepage,article'],
            'image' => ['nullable', 'image', 'max:5120'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
        if ($request->hasFile('image')) {
            $previousImage = $advertisement->image_path;
            $imagePath = Storage::disk('public')->putFile('ads', $request->file('image'));

            if (! is_string($imagePath)) {
                throw new RuntimeException('Unable to store the replacement advertisement image.');
            }

            $data['image_path'] = $imagePath;
        }
        $data['active'] = $request->boolean('active');
        unset($data['image']);
        $advertisement->update($data);

        if (isset($previousImage)) {
            Storage::disk('public')->delete($previousImage);
        }

        return back()->with('status', 'Advertisement updated.');
    }

    public function destroy(Advertisement $advertisement): RedirectResponse
    {
        Storage::disk('public')->delete($advertisement->image_path);
        $advertisement->delete();

        return back()->with('status', 'Advertisement deleted.');
    }
}
