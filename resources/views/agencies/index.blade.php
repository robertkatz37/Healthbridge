<x-public-layout title="Find Care — HealthsBridge" seo-description="Browse verified senior care agencies — assisted living, memory care, home care, hospice, and more." :breadcrumb-items="$breadcrumbs->items()">
    <div class="mb-4">
        <h2 class="fw-bold" style="font-family:'Fraunces',serif;color:var(--hb-gray-900);">Find Trusted Care Providers</h2>
        <p style="color:var(--hb-gray-600);">Browse verified healthcare agencies near you.</p>
    </div>

    {{-- Filters --}}
    <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('agencies.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="hb-form-label">Category</label>
                    <select name="category" class="hb-form-control">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->code }}" {{ request('category') === $cat->code ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="hb-form-label">City</label>
                    <input type="text" name="city" value="{{ request('city') }}" class="hb-form-control" placeholder="e.g. Austin">
                </div>
                <div class="col-md-2">
                    <label class="hb-form-label">State</label>
                    <input type="text" name="state" value="{{ request('state') }}" class="hb-form-control" placeholder="TX" maxlength="2">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;flex:1;">
                        <i class="bi bi-search me-1"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Results --}}
    @if($agencies->isEmpty())
        <div class="text-center py-5">
            <i class="bi bi-building" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
            <p class="mt-2" style="color:var(--hb-gray-600);">No agencies found matching your search.</p>
        </div>
    @else
        <div class="row g-3">
            @foreach($agencies as $agency)
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('agencies.show', $agency) }}" class="text-decoration-none">
                        <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);overflow:hidden;">
                            @if($agency->is_featured)
                                <div style="background:var(--hb-gold-500);color:white;font-size:0.7rem;font-weight:700;padding:0.3rem 0.75rem;">
                                    <i class="bi bi-star-fill me-1"></i>FEATURED
                                </div>
                            @endif
                            <div class="card-body p-4">
                                <div class="fw-bold mb-1" style="color:var(--hb-gray-900);font-size:1.05rem;">{{ $agency->name }}</div>
                                <div style="font-size:0.8rem;color:var(--hb-gray-600);margin-bottom:0.5rem;">
                                    <i class="bi bi-geo-alt me-1"></i>{{ $agency->city }}, {{ $agency->state }}
                                </div>
                                <span class="hb-badge-verified">{{ $agency->category?->name }}</span>
                                @if($agency->min_monthly_cost)
                                    <div style="font-size:0.85rem;font-weight:600;color:var(--hb-emerald-700);margin-top:0.75rem;">
                                        From ${{ number_format($agency->min_monthly_cost, 0) }}/mo
                                    </div>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $agencies->links() }}</div>
    @endif
</x-public-layout>
