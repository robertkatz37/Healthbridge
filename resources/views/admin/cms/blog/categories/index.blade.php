<x-admin-layout title="Blog Categories">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.cms.blog.posts.index') }}" class="hb-link">Blog Posts</a></li>
        <li class="breadcrumb-item active">Categories</li>
    @endslot

    @if(session('status'))
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000"><i class="bi bi-check-circle me-2"></i>Done.</div>
    @endif
    @if($errors->any())
        <div class="hb-alert hb-alert-danger mb-4">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Add Category</h6>
                    <form method="POST" action="{{ route('admin.cms.blog.categories.store') }}">
                        @csrf
                        <input type="text" name="name" class="hb-form-control mb-2" placeholder="Category name" required>
                        <button type="submit" class="btn btn-primary w-100" style="border-radius:0.625rem;">Add</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-0">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <tbody>
                            @foreach($categories as $category)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                    <td class="px-4 py-3 fw-bold">{{ $category->name }}</td>
                                    <td class="px-4 py-3" style="color:var(--hb-gray-600);">{{ $category->posts_count }} posts</td>
                                    <td class="px-4 py-3 text-end">
                                        <form method="POST" action="{{ route('admin.cms.blog.categories.destroy', $category) }}" class="d-inline" onsubmit="return confirm('Delete this category?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:0.5rem;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
