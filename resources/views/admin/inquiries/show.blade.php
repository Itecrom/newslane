@extends('layouts.admin')

@section('title', 'Review inquiry | NEWSLANE')
@section('admin_title', 'Review request')
@section('admin_action')<a class="button button-light" href="{{ route('admin.inquiries.index') }}">← All inquiries</a>@endsection

@section('admin_content')
    <div class="admin-content-grid inquiry-detail-grid">
        <section class="admin-panel inquiry-message">
            <p class="eyebrow">{{ ucfirst($contact->request_type) }} · {{ $contact->created_at->format('d F Y, H:i') }} CAT</p>
            <h2>{{ $contact->subject }}</h2>
            <div class="inquiry-sender"><strong>{{ $contact->name }}</strong><a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>@if($contact->phone)<a href="tel:{{ $contact->phone }}">{{ $contact->phone }}</a>@endif @if($contact->organization)<span>{{ $contact->organization }}</span>@endif</div>
            @if ($contact->news_headline)<div class="inquiry-field"><small>PROPOSED HEADLINE</small><p>{{ $contact->news_headline }}</p></div>@endif
            @if ($contact->category)<div class="inquiry-field"><small>SUGGESTED SECTION</small><p>{{ $contact->category->name }}</p></div>@endif
            <div class="inquiry-field"><small>MESSAGE</small><p class="inquiry-message-body">{!! nl2br(e($contact->message)) !!}</p></div>
            @if ($contact->source_attribution)<div class="inquiry-field"><small>SOURCE & IMAGE CREDITS</small><p class="inquiry-message-body">{!! nl2br(e($contact->source_attribution)) !!}</p></div>@endif
            @if ($contact->campaign_budget || $contact->campaign_start || $contact->campaign_end)<div class="inquiry-field"><small>CAMPAIGN DETAILS</small><p>Budget: {{ $contact->campaign_budget ?: 'Not provided' }}<br>Preferred dates: {{ $contact->campaign_start?->format('d M Y') ?: 'Not set' }} — {{ $contact->campaign_end?->format('d M Y') ?: 'Not set' }}</p></div>@endif
            @if ($contact->attachment_path)<div class="inquiry-field"><small>PRIVATE ATTACHMENT</small><a class="button button-light" href="{{ route('admin.inquiries.attachment', $contact) }}">Download attachment ↧</a><p class="form-note">Do not redistribute submitted files without permission.</p></div>@endif
        </section>
        <aside class="admin-panel category-create"><p class="eyebrow">EDITORIAL FOLLOW-UP</p><h2>Request status</h2>
            <form action="{{ route('admin.inquiries.update', $contact) }}" method="post" class="stack-form">@csrf @method('PATCH')
                <label>Status<select name="status"><option value="new" @selected($contact->status === 'new')>New</option><option value="reviewing" @selected($contact->status === 'reviewing')>In review</option><option value="closed" @selected($contact->status === 'closed')>Closed</option></select></label>
                <button class="button button-blue" type="submit">Save status</button>
            </form>
            <p class="form-note">Reply directly using the sender’s email address. Verify submissions before further circulation.</p>
        </aside>
    </div>
@endsection
