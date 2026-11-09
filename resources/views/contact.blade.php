@extends('layouts.app')

@section('title', 'Contact NEWSLANE | News tips, advertising & inquiries')
@section('meta_description', 'Contact NEWSLANE for general inquiries, advertising opportunities and original news submissions from Malawi, Africa and the world.')

@section('content')
    <section class="shell contact-page">
        <header class="listing-heading contact-heading">
            <p class="eyebrow">NEWSLANE / GET IN TOUCH</p>
            <h1>Let’s talk.</h1>
            <p>News tips, advertising opportunities or a question for our team? Choose the right desk and we’ll make sure your message gets there.</p>
        </header>
        <div class="contact-grid">
            <section class="contact-form-panel">
                <p class="eyebrow">WRITE TO THE NEWSROOM</p>
                <h2>Send us a message</h2>
                <form action="{{ route('contact.store') }}" method="post" enctype="multipart/form-data" class="contact-form">@csrf
                    <label class="contact-type-label">What can we help with?
                        <select name="request_type" data-contact-type required>
                            <option value="general" @selected(old('request_type', request('type')) === 'general')>General inquiry</option>
                            <option value="advertising" @selected(old('request_type', request('type')) === 'advertising')>Advertising & partnerships</option>
                            <option value="news" @selected(old('request_type', request('type')) === 'news')>News tip or original story submission</option>
                        </select>
                    </label>
                    <div class="contact-fields-pair">
                        <label>Your name<input name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name"></label>
                        <label>Email address<input name="email" type="email" value="{{ old('email') }}" required maxlength="254" autocomplete="email"></label>
                    </div>
                    <div class="contact-fields-pair">
                        <label>Phone (optional)<input name="phone" type="tel" value="{{ old('phone') }}" maxlength="40" autocomplete="tel"></label>
                        <label>Organization (optional)<input name="organization" value="{{ old('organization') }}" maxlength="180" autocomplete="organization"></label>
                    </div>
                    <div data-contact-section="news" @if (old('request_type', request('type')) !== 'news') hidden @endif>
                        <div class="contact-fields-pair">
                            <label>Proposed headline<input name="news_headline" value="{{ old('news_headline') }}" maxlength="255" placeholder="What is your story about?"></label>
                            <label>Suggested section<select name="category_id"><option value="">Choose a section</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                        </div>
                        <label>Original sources and image credits<textarea name="source_attribution" rows="3" maxlength="2000" placeholder="Link to your original source material. Credit every photograph, video, document or third-party source."></textarea></label>
                        <label class="rights-confirmation"><input type="checkbox" name="rights_confirmation" value="1" @checked(old('rights_confirmation'))><span>I confirm that this submission is my original work, or that I have permission to share it. The material does not copy or reproduce another publisher’s article or photographs without permission.</span></label>
                    </div>
                    <div data-contact-section="advertising" @if (old('request_type', request('type')) !== 'advertising') hidden @endif>
                        <div class="contact-fields-pair">
                            <label>Indicative budget (optional)<input name="campaign_budget" value="{{ old('campaign_budget') }}" maxlength="120" placeholder="e.g. MWK 500,000"></label>
                            <label>Preferred start date (optional)<input name="campaign_start" type="date" value="{{ old('campaign_start') }}"></label>
                        </div>
                        <label>Preferred end date (optional)<input name="campaign_end" type="date" value="{{ old('campaign_end') }}"></label>
                    </div>
                    <label>Subject<input name="subject" value="{{ old('subject') }}" required maxlength="200" placeholder="A short subject for your message"></label>
                    <label data-contact-message-label>Your message<textarea name="message" rows="7" required minlength="20" maxlength="5000" placeholder="Tell us what we need to know (20–5,000 characters).">{{ old('message') }}</textarea></label>
                    <label>Supporting file (optional)<input name="attachment" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.mp4"><span class="field-help">JPG, PNG, WebP, PDF, DOC, DOCX or MP4. Maximum 10 MB. Files are private and reviewed by the newsroom.</span></label>
                    <p class="form-note">Your submission goes to the appropriate NEWSLANE desk. Please do not send passwords, financial account details or urgent emergency reports.</p>
                    <button class="button button-blue" type="submit">Send to NEWSLANE <span>→</span></button>
                </form>
            </section>
            <aside class="contact-aside">
                <section class="contact-desk-card"><p class="eyebrow">THE RIGHT DESK</p><h2>Talk to our team.</h2><p>We’ll forward your message to the right people.</p>
                    <a class="contact-desk-link" href="mailto:info@newslane.africa"><span>General inquiries</span><strong>info@newslane.africa</strong><small>Questions, feedback and general correspondence</small></a>
                    <a class="contact-desk-link" href="mailto:sales@newslane.africa"><span>Advertising & partnerships</span><strong>sales@newslane.africa</strong><small>Placements, campaigns and commercial partnerships</small></a>
                    <a class="contact-desk-link" href="mailto:editor@newslane.africa"><span>Newsroom & submissions</span><strong>editor@newslane.africa</strong><small>Original news tips, reporting and corrections</small></a>
                </section>
                <section class="contact-standards"><span class="brand-mark">N</span><div><h3>Independent. Accountable.</h3><p>Our editors verify tips, credit original sources and respect the work of other journalists.</p></div></section>
            </aside>
        </div>
    </section>
@endsection
