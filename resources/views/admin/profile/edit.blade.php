@extends('layouts.admin')

@section('title', 'My profile | NEWSLANE')
@section('admin_title', 'My profile')

@section('admin_content')
    <section class="admin-panel profile-settings">
        <p class="eyebrow">NEWSROOM ACCOUNT</p>
        <h2>Update your details</h2>
        <form action="{{ route('admin.profile.update') }}" method="post" class="stack-form">
            @csrf
            @method('PUT')
            <label>Full name<input name="name" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name"></label>
            <label>Work email<input name="email" type="email" value="{{ old('email', $user->email) }}" required maxlength="254" autocomplete="email"></label>
            <label>Short biography <span class="field-help">Up to 500 characters.</span><textarea name="bio" rows="4" maxlength="500">{{ old('bio', $user->bio) }}</textarea></label>
            <label>Current password <span class="field-help">Confirm your identity to save profile changes.</span><input name="current_password" type="password" required autocomplete="current-password"></label>
            <label>New password <span class="field-help">Leave blank to keep your current password. If changing it, use at least 12 characters with uppercase, lowercase, a number and a symbol.</span><input name="password" type="password" minlength="12" autocomplete="new-password"></label>
            <label>Confirm new password<input name="password_confirmation" type="password" minlength="12" autocomplete="new-password"></label>
            <button class="button button-blue" type="submit">Save profile <span>→</span></button>
        </form>
    </section>
@endsection
