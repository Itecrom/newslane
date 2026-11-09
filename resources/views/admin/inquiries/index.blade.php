@extends('layouts.admin')

@section('title', 'Inquiries | NEWSLANE')
@section('admin_title', 'Inquiries & submissions')

@section('admin_content')
    <div class="inquiry-counts">
        <div><span>New</span><strong>{{ $counts['new'] }}</strong></div>
        <div><span>In review</span><strong>{{ $counts['reviewing'] }}</strong></div>
        <div><span>Closed</span><strong>{{ $counts['closed'] }}</strong></div>
    </div>
    <section class="admin-panel table-panel"><div class="panel-heading"><div><p class="eyebrow">ADMINISTRATOR ACCESS ONLY</p><h2>Messages, proposals & news submissions</h2></div><span class="muted-copy">{{ $messages->total() }} total</span></div>
        <div class="table-scroll"><table><thead><tr><th>Request</th><th>From</th><th>Subject</th><th>Received</th><th>Status</th><th>Review</th></tr></thead><tbody>
        @forelse ($messages as $contact)
            <tr><td><span class="status-badge {{ $contact->request_type === 'advertising' ? 'status-reviewing' : ($contact->request_type === 'news' ? 'status-pending' : '') }}">{{ ucfirst($contact->request_type) }}</span></td><td class="table-story"><strong>{{ $contact->name }}</strong><small>{{ $contact->email }}</small></td><td>{{ $contact->subject }}</td><td>{{ $contact->created_at->format('d M Y · H:i') }}</td><td><span class="status-badge status-{{ $contact->status }}">{{ ucfirst($contact->status) }}</span></td><td><a href="{{ route('admin.inquiries.show', $contact) }}">Review →</a></td></tr>
        @empty <tr><td colspan="6"><div class="table-empty"><h3>No inquiries yet</h3><p>Contact forms and story submissions will appear here.</p></div></td></tr> @endforelse
        </tbody></table></div>
        <div class="pagination-wrap">{{ $messages->links() }}</div>
    </section>
@endsection
