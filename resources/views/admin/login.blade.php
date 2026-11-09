@extends('layouts.app')

@section('title', 'Editorial sign in | NEWSLANE')
@section('meta_description', 'Secure NEWSLANE newsroom access.')

@section('content')
    <section class="login-page">
        <div class="login-card">
            <img src="{{ asset('images/newslane-mark.svg') }}" width="48" height="48" alt="NEWSLANE">
            <p class="eyebrow">SECURE NEWSROOM</p>
            <h1>Editorial sign in</h1>
            <p>Access is limited to authorized newsroom staff.</p>
            <form action="{{ route('admin.login.store') }}" method="post" class="stack-form">@csrf
                <label>Email address<input type="email" name="email" value="{{ old('email') }}" required maxlength="254" autocomplete="username" autofocus></label>
                <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
                <label class="checkbox-label"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
                <button class="button button-blue" type="submit">Sign in securely <span>→</span></button>
            </form>
            <small>Never share your newsroom credentials. Access attempts are rate limited.</small>
        </div>
    </section>
@endsection
