<x-admin-layout title="Pages">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Pages</li>
    @endslot

    @if(session('status'))
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000"><i class="bi bi-check-circle me-2"></i>Done.</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex flex-wrap gap-2">
            @foreach(['' => 'All', 'draft' => 'Draft', 'published' => 'Published', 'scheduled' => 'Scheduled', 'archived' => 'Archived'] as $key => $label)
                <a href="{{ route('admin.cms.pages.index', ['status' => $key]) }}"
                   class="btn btn-sm {{ request('status', '') === $key ? 'btn-primary' : 'btn-outline-secondary' }}" style="border-radius:999px;">
                    {{ $label }}
                </a>
            @endforeach
        </div>
        <a href="{{ route('admin.cms.pages.create') }}" class="btn btn-primary" style="border-radius:0.625rem;"><i class="bi bi-plus-lg me-1"></i>New Page</a>
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            @if($pages->isEmpty())
                <div class="text-center py-5"><p style="color:var(--hb-gray-600);margin:0;">No pages yet.</p></div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3">Title</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Updated</th>
                                <th class="px-4 py-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pages as $page)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.cms.pages.edit', $page) }}" class="hb-link fw-bold">{{ $page->title }}</a>
                                        <div style="font-size:0.75rem;color:var(--hb-gray-600);">/{{ $page->page_type === 'home' ? '' : $page->slug }}</div>
                                    </td>
                                    <td class="px-4 py-3">{{ ucfirst($page->page_type) }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $badgeColors = ['draft' => ['#F3F4F6', '#374151'], 'published' => ['var(--hb-emerald-100)', 'var(--hb-emerald-700)'], 'scheduled' => ['#FFFBEB', '#92400E'], 'archived' => ['#FEF2F2', '#991B1B']];
                                            [$bg, $text] = $badgeColors[$page->status] ?? ['#F3F4F6', '#374151'];
                                        @endphp
                                        <span style="font-size:0.75rem;font-weight:600;background:{{ $bg }};color:{{ $text }};padding:0.2rem 0.6rem;border-radius:999px;">{{ ucfirst($page->status) }}</span>
                                    </td>
                                    <td class="px-4 py-3" style="color:var(--hb-gray-600);">{{ $page->updated_at->diffForHumans() }}</td>
                                    <td class="px-4 py-3 text-end">
                                        <a href="{{ route('admin.cms.pages.edit', $page) }}" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">Edit</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">{{ $pages->links() }}</div>
            @endif
        </div>
    </div>
</x-admin-layout>
