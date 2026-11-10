@extends('layouts.admin')

@section('title', 'Advertising | NEWSLANE')
@section('admin_title', 'Advertising')

@section('admin_content')
    <div class="admin-content-grid ads-layout">
        <section class="admin-panel"><div class="panel-heading"><div><p class="eyebrow">CAMPAIGNS & PLACEMENTS</p><h2>Active advertisements</h2></div></div>
            @forelse ($advertisements as $advertisement)
                <form class="ad-item" action="{{ route('admin.ads.update', $advertisement) }}" method="post" enctype="multipart/form-data">@csrf @method('PUT')
                    <img src="{{ asset('storage/'.$advertisement->image_path) }}" alt="{{ $advertisement->title }}">
                    <div class="ad-edit-fields"><label>Campaign name<input name="title" value="{{ $advertisement->title }}" required maxlength="160"></label><label>Destination URL<input name="target_url" type="url" value="{{ $advertisement->target_url }}" required maxlength="2048"></label><label>Placement<select name="placement"><option value="homepage" @selected($advertisement->placement === 'homepage')>Homepage</option><option value="article" @selected($advertisement->placement === 'article')>Article pages</option></select></label><label>Replace image<input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/avif"></label><label>Start time<input type="datetime-local" name="starts_at" value="{{ $advertisement->starts_at?->format('Y-m-d\TH:i') }}"></label><label>End time<input type="datetime-local" name="ends_at" value="{{ $advertisement->ends_at?->format('Y-m-d\TH:i') }}"></label><label class="checkbox-label"><input type="checkbox" name="active" value="1" @checked($advertisement->active)> Campaign active</label><div class="ad-actions"><button class="small-action" type="submit">Save campaign</button><button class="small-action small-danger" type="submit" form="delete-ad-{{ $advertisement->id }}">Delete</button></div></div>
                </form><form id="delete-ad-{{ $advertisement->id }}" action="{{ route('admin.ads.destroy', $advertisement) }}" method="post" data-confirm="Delete this advertisement?">@csrf @method('DELETE')</form>
            @empty <p class="admin-quiet">There are no campaigns yet. Add an advertisement to display it on the homepage.</p> @endforelse
            <div class="pagination-wrap">{{ $advertisements->links() }}</div>
        </section>
        <section class="admin-panel category-create"><p class="eyebrow">NEW CAMPAIGN</p><h2>Add advertisement</h2><form action="{{ route('admin.ads.store') }}" method="post" enctype="multipart/form-data" class="stack-form">@csrf
            <label>Campaign name<input name="title" required maxlength="160"></label>
            <label>Destination URL<input name="target_url" type="url" placeholder="https://example.com" required maxlength="2048"></label>
            <label>Placement<select name="placement"><option value="homepage">Homepage</option><option value="article">Article pages</option></select></label>
            <label>Image<input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/avif" required><span class="field-help">Max 5 MB.</span></label>
            <label>Start time <input type="datetime-local" name="starts_at"></label>
            <label>End time <input type="datetime-local" name="ends_at"></label>
            <label class="checkbox-label"><input type="checkbox" name="active" value="1" checked> Campaign active</label>
            <button class="button button-blue" type="submit">Add campaign <span>→</span></button>
        </form></section>
    </div>
@endsection
