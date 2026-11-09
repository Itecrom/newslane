<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContactMessageAdminController extends Controller
{
    public function index(): View
    {
        $messages = ContactMessage::with('category')->latest()->paginate(25);
        $counts = [
            'new' => ContactMessage::where('status', 'new')->count(),
            'reviewing' => ContactMessage::where('status', 'reviewing')->count(),
            'closed' => ContactMessage::where('status', 'closed')->count(),
        ];

        return view('admin.inquiries.index', compact('messages', 'counts'));
    }

    public function show(ContactMessage $contact): View
    {
        $contact->load('category');

        return view('admin.inquiries.show', compact('contact'));
    }

    public function update(Request $request, ContactMessage $contact): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['new', 'reviewing', 'closed'])]]);
        $contact->update($data);

        return back()->with('status', 'Inquiry status updated.');
    }

    public function attachment(ContactMessage $contact): BinaryFileResponse
    {
        abort_unless($contact->attachment_path && Storage::disk('local')->exists($contact->attachment_path), 404);

        return response()->download(
            Storage::disk('local')->path($contact->attachment_path),
            basename($contact->attachment_path),
            ['X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'; sandbox"],
        );
    }
}
