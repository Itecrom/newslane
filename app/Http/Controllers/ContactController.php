<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContactMessage;
use App\Mail\ContactSubmissionNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('contact', ['categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'request_type' => ['required', 'in:general,advertising,news'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'phone' => ['nullable', 'string', 'max:40'],
            'organization' => ['nullable', 'string', 'max:180'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:20', 'max:5000'],
            'news_headline' => ['nullable', 'required_if:request_type,news', 'string', 'max:255'],
            'category_id' => ['nullable', 'required_if:request_type,news', 'integer', 'exists:categories,id'],
            'source_attribution' => ['nullable', 'required_if:request_type,news', 'string', 'max:2000'],
            'campaign_budget' => ['nullable', 'string', 'max:120'],
            'campaign_start' => ['nullable', 'date'],
            'campaign_end' => ['nullable', 'date', 'after_or_equal:campaign_start'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,mp4', 'max:10240'],
            'rights_confirmation' => ['nullable', 'accepted_if:request_type,news'],
        ]);

        if ($request->hasFile('attachment')) {
            $attachmentPath = Storage::disk('local')->putFile('contact-submissions', $request->file('attachment'));

            if (! is_string($attachmentPath)) {
                throw new RuntimeException('Unable to securely store the contact submission attachment.');
            }

            $data['attachment_path'] = $attachmentPath;
        }

        unset($data['attachment'], $data['rights_confirmation']);
        $contact = ContactMessage::create($data + ['status' => 'new']);

        $recipient = match ($contact->request_type) {
            'advertising' => 'sales@newslane.africa',
            'news' => 'editor@newslane.africa',
            default => 'info@newslane.africa',
        };

        Mail::to($recipient)->send(new ContactSubmissionNotification($contact));

        return redirect()->route('contact')->with('status', 'Thank you. Your message has been received and forwarded to our team.');
    }
}
