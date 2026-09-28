<x-admin-layout title="Review Moderation">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Reviews</li>
    @endslot

    @if(session('status'))
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000">
            <i class="bi bi-check-circle me-2"></i>Done.
        </div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-4">
        @foreach([
            ['key' => 'pending', 'label' => 'Pending', 'count' => $counts['pending']],
            ['key' => 'published', 'label' => 'Published', 'count' => null],
            ['key' => 'rejected', 'label' => 'Rejected', 'count' => null],
            ['key' => 'hidden', 'label' => 'Hidden', 'count' => null],
            ['key' => 'featured', 'label' => 'Featured', 'count' => null],
            ['key' => 'reported', 'label' => 'Reported', 'count' => $counts['reported']],
        ] as $tab)
            <a href="{{ route('admin.reviews.index', ['filter' => $tab['key']]) }}"
               class="btn btn-sm {{ $filter === $tab['key'] ? 'btn-primary' : 'btn-outline-secondary' }}"
               style="border-radius:999px;">
                {{ $tab['label'] }}{{ $tab['count'] !== null ? ' (' . $tab['count'] . ')' : '' }}
            </a>
        @endforeach
    </div>

    @if($filter === 'reported')
        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-0">
                @if($reportedReviews->isEmpty())
                    <div class="text-center py-5"><p style="color:var(--hb-gray-600);margin:0;">No reported reviews.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table mb-0" style="font-size:0.875rem;">
                            <thead style="background:var(--hb-gray-50);">
                                <tr>
                                    <th class="px-4 py-3">Review</th>
                                    <th class="px-4 py-3">Reason</th>
                                    <th class="px-4 py-3">Reported By</th>
                                    <th class="px-4 py-3 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportedReviews as $report)
                                    <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('admin.reviews.show', $report->review) }}" class="hb-link">{{ $report->review->title }}</a>
                                            <div style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $report->review->agency->name }}</div>
                                        </td>
                                        <td class="px-4 py-3">{{ $report->reason->label() }}</td>
                                        <td class="px-4 py-3">{{ $report->reporter->name }}</td>
                                        <td class="px-4 py-3 text-end">
                                            <form method="POST" action="{{ route('admin.review-reports.resolve', $report) }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="status" value="dismissed">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary" style="border-radius:0.5rem;">Dismiss</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.review-reports.resolve', $report) }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="status" value="reviewed">
                                                <button type="submit" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">Mark Reviewed</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-0">
                @if($reviews->isEmpty())
                    <div class="text-center py-5"><p style="color:var(--hb-gray-600);margin:0;">No reviews in this view.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table mb-0" style="font-size:0.875rem;">
                            <thead style="background:var(--hb-gray-50);">
                                <tr>
                                    <th class="px-4 py-3">Title</th>
                                    <th class="px-4 py-3">Agency</th>
                                    <th class="px-4 py-3">Family</th>
                                    <th class="px-4 py-3">Rating</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reviews as $review)
                                    @php $c = $review->status->badgeColor(); @endphp
                                    <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('admin.reviews.show', $review) }}" class="hb-link fw-bold">{{ $review->title }}</a>
                                        </td>
                                        <td class="px-4 py-3">{{ $review->agency->name }}</td>
                                        <td class="px-4 py-3">{{ $review->display_name }}</td>
                                        <td class="px-4 py-3">{{ number_format((float) $review->overall_rating, 1) }} <i class="bi bi-star-fill" style="color:var(--hb-gold-500);font-size:0.75rem;"></i></td>
                                        <td class="px-4 py-3">
                                            <span style="font-size:0.75rem;font-weight:600;background:{{ $c['bg'] }};color:{{ $c['text'] }};padding:0.2rem 0.6rem;border-radius:999px;">{{ $review->status->label() }}</span>
                                            @if($review->is_featured)<span class="hb-badge-verified ms-1">Featured</span>@endif
                                        </td>
                                        <td class="px-4 py-3 text-end">
                                            <a href="{{ route('admin.reviews.show', $review) }}" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">Review</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3">{{ $reviews->links() }}</div>
                @endif
            </div>
        </div>
    @endif
</x-admin-layout>
