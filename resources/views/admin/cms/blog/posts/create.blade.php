<x-admin-layout title="New Blog Post">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.cms.blog.posts.index') }}" class="hb-link">Blog Posts</a></li>
        <li class="breadcrumb-item active">New Post</li>
    @endslot

    <form method="POST" action="{{ route('admin.cms.blog.posts.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.cms.blog.posts._form', ['post' => null])
    </form>
</x-admin-layout>
