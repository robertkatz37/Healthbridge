<x-admin-layout title="Blog Posts">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Blog Posts</li>
    @endslot

    @if(session('status'))
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000"><i class="bi bi-check-circle me-2"></i>Done.</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex flex-wrap gap-2">
            @foreach(['' => 'All', 'draft' => 'Draft', 'scheduled' => 'Scheduled', 'published' => 'Published'] as $key => $label)
                <a href="{{ route('admin.cms.blog.posts.index', ['status' => $key]) }}"
                   class="btn btn-sm {{ request('status', '') === $key ? 'btn-primary' : 'btn-outline-secondary' }}" style="border-radius:999px;">{{ $label }}</a>
            @endforeach
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.cms.blog.categories.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius:0.625rem;">Categories</a>
            <a href="{{ route('admin.cms.blog.tags.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius:0.625rem;">Tags</a>
            <a href="{{ route('admin.cms.blog.posts.create') }}" class="btn btn-primary" style="border-radius:0.625rem;"><i class="bi bi-plus-lg me-1"></i>New Post</a>
        </div>
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            @if($posts->isEmpty())
                <div class="text-center py-5"><p style="color:var(--hb-gray-600);margin:0;">No blog posts yet.</p></div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3">Title</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Author</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($posts as $post)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.cms.blog.posts.edit', $post) }}" class="hb-link fw-bold">{{ $post->title }}</a>
                                        @if($post->is_featured)<span class="hb-badge-verified ms-1">Featured</span>@endif
                                    </td>
                                    <td class="px-4 py-3">{{ $post->category->name }}</td>
                                    <td class="px-4 py-3">{{ $post->author->name }}</td>
                                    <td class="px-4 py-3"><span class="hb-badge-verified">{{ ucfirst($post->status) }}</span></td>
                                    <td class="px-4 py-3 text-end">
                                        <a href="{{ route('admin.cms.blog.posts.edit', $post) }}" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">Edit</a>
                                        <form method="POST" action="{{ route('admin.cms.blog.posts.destroy', $post) }}" class="d-inline" onsubmit="return confirm('Delete this post?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:0.5rem;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">{{ $posts->links() }}</div>
            @endif
        </div>
    </div>
</x-admin-layout>
