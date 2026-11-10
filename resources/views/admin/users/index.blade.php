@extends('layouts.admin')

@section('title', 'Staff accounts | NEWSLANE')
@section('admin_title', 'Staff accounts')

@section('admin_content')
    <div class="admin-content-grid staff-layout">
        <section class="admin-panel table-panel"><div class="panel-heading"><div><p class="eyebrow">ACCESS CONTROL</p><h2>Newsroom team</h2></div><span class="muted-copy">{{ $users->total() }} accounts</span></div>
            <div class="staff-list">
                @foreach ($users as $user)
                    <form class="staff-row" action="{{ route('admin.users.update', $user) }}" method="post">@csrf @method('PATCH')
                        <div class="staff-identity"><span class="author-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span><div><strong>{{ $user->name }} @if ($user->is(auth()->user()))<small>(you)</small>@endif</strong><span>{{ $user->email }}</span></div></div>
                        <label>Role<select name="role" @disabled($user->is(auth()->user()))><option value="intern" @selected($user->role === 'intern')>Intern · drafts only</option><option value="reporter" @selected($user->role === 'reporter')>Reporter · drafts only</option><option value="editor" @selected($user->role === 'editor')>Editor</option><option value="admin" @selected($user->role === 'admin')>Administrator</option></select></label>
                        @if ($user->is(auth()->user()))<input type="hidden" name="role" value="admin">@endif
                        <label class="checkbox-label"><input type="checkbox" name="is_active" value="1" @checked($user->is_active) @disabled($user->is(auth()->user()))> Active</label>
                        @if ($user->is(auth()->user()))<input type="hidden" name="is_active" value="1">@endif
                        <button class="small-action" type="submit" @disabled($user->is(auth()->user()))>Save</button>
                        @if (! $user->is_active)<span class="status-badge status-closed">Disabled</span>@endif
                    </form>
                @endforeach
            </div>
            <div class="pagination-wrap">{{ $users->links() }}</div>
            <p class="form-note">Reporters and interns can manage only their own drafts. Editors can publish and manage newsroom content; only administrators can manage staff accounts, inquiries and Google AdSense. At least one active administrator must remain.</p>
        </section>
        <section class="admin-panel category-create"><p class="eyebrow">INVITE WITH CARE</p><h2>Create a staff account</h2><form action="{{ route('admin.users.store') }}" method="post" class="stack-form">@csrf
            <label>Full name<input name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name"></label>
            <label>Work email<input name="email" type="email" value="{{ old('email') }}" required maxlength="254" autocomplete="email"></label>
            <label>Role<select name="role"><option value="intern">Intern · drafts only</option><option value="reporter">Reporter · drafts only</option><option value="editor">Editor</option><option value="admin">Administrator</option></select></label>
            <label>Password<input name="password" type="password" required minlength="12" autocomplete="new-password"><span class="field-help">12+ characters with uppercase, lowercase, a number and a symbol.</span></label>
            <label>Confirm password<input name="password_confirmation" type="password" required minlength="12" autocomplete="new-password"></label>
            <button class="button button-blue" type="submit">Create account <span>→</span></button>
        </form></section>
    </div>
@endsection
