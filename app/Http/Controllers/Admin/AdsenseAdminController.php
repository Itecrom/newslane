<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdsenseSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdsenseAdminController extends Controller
{
    public function edit(): View
    {
        $setting = AdsenseSetting::query()->firstOrNew(
            ['id' => 1],
            ['enabled' => config('services.adsense.enabled')]
        );

        return view('admin.adsense.edit', compact('setting'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);
        $setting = AdsenseSetting::query()->firstOrNew(['id' => 1]);
        $setting->enabled = $request->boolean('enabled');
        $setting->save();

        return back()->with('status', 'Google AdSense settings updated.');
    }
}
