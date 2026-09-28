<x-admin-layout title="Edit — {{ $post->title }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.cms.blog.posts.index') }}" class="hb-link">Blog Posts</a></li>
        <li class="breadcrumb-item active">{{ $post->title }}</li>
    @endslot

    @if(session('status'))
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000"><i class="bi bi-check-circle me-2"></i>Done.</div>
    @endif

    <form method="POST" action="{{ route('admin.cms.blog.posts.update', $post) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.cms.blog.posts._form', ['post' => $post])
    </form>
</x-admin-layout>
