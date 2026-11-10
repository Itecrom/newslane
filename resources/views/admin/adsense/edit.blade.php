@extends('layouts.admin')

@section('title', 'Google AdSense | NEWSLANE')
@section('admin_title', 'Google AdSense')

@section('admin_content')
    <section class="admin-panel adsense-settings">
        <p class="eyebrow">GOOGLE AUTO ADS</p>
        <h2>Manage sitewide AdSense</h2>
        <p>When enabled, NEWSLANE loads the Google AdSense script with the configured publisher ID on public pages. Google Auto ads placement and eligibility are managed in your AdSense account.</p>
        <p class="adsense-publisher"><strong>Publisher ID</strong><code>{{ config('services.adsense.client_id') }}</code></p>
        <form action="{{ route('admin.adsense.update') }}" method="post" class="stack-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="enabled" value="0">
            <label class="checkbox-label"><input type="checkbox" name="enabled" value="1" @checked($setting->enabled)> Load Google AdSense on public pages</label>
            <button class="button button-blue" type="submit">Save AdSense settings <span>→</span></button>
        </form>
        <p class="form-note">The publisher ID is public. Do not paste arbitrary script or HTML here. Ads begin displaying only after Google approves the site and Auto ads are enabled in the AdSense account.</p>
    </section>
@endsection
