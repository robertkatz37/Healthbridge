<x-admin-layout title="Blog Tags">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.cms.blog.posts.index') }}" class="hb-link">Blog Posts</a></li>
        <li class="breadcrumb-item active">Tags</li>
    @endslot

    @if(session('status'))
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000"><i class="bi bi-check-circle me-2"></i>Done.</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Add Tag</h6>
                    <form method="POST" action="{{ route('admin.cms.blog.tags.store') }}">
                        @csrf
                        <input type="text" name="name" class="hb-form-control mb-2" placeholder="Tag name" required>
                        <button type="submit" class="btn btn-primary w-100" style="border-radius:0.625rem;">Add</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($tags as $tag)
                            <div class="d-flex align-items-center gap-1 hb-badge-verified">
                                {{ $tag->name }} ({{ $tag->posts_count }})
                                <form method="POST" action="{{ route('admin.cms.blog.tags.destroy', $tag) }}" onsubmit="return confirm('Delete this tag?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent" style="color:var(--hb-danger);"><i class="bi bi-x"></i></button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
