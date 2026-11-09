<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:254']]);
        $email = Str::lower($data['email']);
        $subscriber = Subscriber::firstOrNew(['email' => $email]);

        if ($subscriber->verified_at) {
            return back()->with('status', 'This email is already subscribed.');
        }

        $verificationToken = Str::random(64);
        $unsubscribeToken = Str::random(64);
        $subscriber->verification_token_hash = hash('sha256', $verificationToken);
        $subscriber->unsubscribe_token_hash = hash('sha256', $unsubscribeToken);
        $subscriber->save();

        $confirmationUrl = URL::temporarySignedRoute(
            'newsletter.confirm',
            now()->addDay(),
            ['subscriber' => $subscriber->id, 'token' => $verificationToken],
        );
        $unsubscribeUrl = URL::signedRoute(
            'newsletter.unsubscribe',
            ['subscriber' => $subscriber->id, 'token' => $unsubscribeToken],
        );

        Mail::raw(
            "Confirm your NEWSLANE newsletter subscription:\n{$confirmationUrl}\n\nTo unsubscribe, use this link:\n{$unsubscribeUrl}",
            fn ($message) => $message->to($email)->subject('Confirm your NEWSLANE subscription'),
        );

        return back()->with('status', 'Check your email to confirm your subscription.');
    }

    public function confirm(Request $request, Subscriber $subscriber, string $token): View
    {
        abort_unless(
            $subscriber->verification_token_hash
                && hash_equals($subscriber->verification_token_hash, hash('sha256', $token)),
            403,
        );

        $subscriber->forceFill([
            'verified_at' => now(),
            'verification_token_hash' => null,
        ])->save();

        return view('newsletter-result', ['message' => 'Your subscription is confirmed. Welcome to NEWSLANE.']);
    }

    public function unsubscribe(Subscriber $subscriber, string $token): View
    {
        abort_unless(hash_equals($subscriber->unsubscribe_token_hash, hash('sha256', $token)), 403);
        $subscriber->delete();

        return view('newsletter-result', ['message' => 'You have been unsubscribed from NEWSLANE emails.']);
    }
}
