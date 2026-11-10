<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserAdminController extends Controller
{
    public function index(): View
    {
        $users = User::orderBy('name')->paginate(30);

        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:254', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'editor', 'reporter', 'intern'])],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
        ]);
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = true;
        User::create($data);

        return back()->with('status', 'Newsroom account created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['admin', 'editor', 'reporter', 'intern'])],
            'is_active' => ['required', 'boolean'],
        ]);
        $isActive = $request->boolean('is_active');

        if ($user->is($request->user()) && ($data['role'] !== 'admin' || ! $isActive)) {
            return back()->withErrors(['user' => 'You cannot remove your own administrator access.']);
        }

        $wasActiveAdmin = $user->isAdmin() && $user->is_active;
        $willRemainActiveAdmin = $data['role'] === 'admin' && $isActive;

        if ($wasActiveAdmin && ! $willRemainActiveAdmin) {
            $canDemote = DB::transaction(function () use ($user, $data, $isActive): bool {
                $activeAdmins = User::where('role', 'admin')->where('is_active', true)->lockForUpdate()->get(['id']);

                if ($activeAdmins->count() <= 1) {
                    return false;
                }

                $user->update(['role' => $data['role'], 'is_active' => $isActive]);

                return true;
            });

            if (! $canDemote) {
                return back()->withErrors(['user' => 'At least one active administrator must remain.']);
            }
        } else {
            $user->update(['role' => $data['role'], 'is_active' => $isActive]);
        }

        return back()->with('status', 'Newsroom account updated.');
    }
}
