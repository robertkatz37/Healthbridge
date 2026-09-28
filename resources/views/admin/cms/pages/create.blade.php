<x-admin-layout title="New Page">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.cms.pages.index') }}" class="hb-link">Pages</a></li>
        <li class="breadcrumb-item active">New Page</li>
    @endslot

    <form method="POST" action="{{ route('admin.cms.pages.store') }}">
        @csrf
        @include('admin.cms.pages._form', ['page' => null])
    </form>
</x-admin-layout>
