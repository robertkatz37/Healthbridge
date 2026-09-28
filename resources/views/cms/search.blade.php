<x-public-layout title="Search{{ $query ? ' — ' . $query : '' }} | HealthsBridge" robots="noindex,follow">
    <h1 class="fw-bold mb-4" style="font-family:'Fraunces',serif;color:var(--hb-gray-900);">
        @if($query)
            Search results for "{{ $query }}"
        @else
            Search
        @endif
    </h1>

    <form method="GET" class="mb-4" style="max-width:500px;">
        <div class="input-group">
            <input type="search" name="q" class="hb-form-control" placeholder="Search pages, articles, agencies, cities, FAQs..." value="{{ $query }}">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>

    @if($query && $results->isEmpty())
        <p style="color:var(--hb-gray-600);">No results found for "{{ $query }}".</p>
    @elseif($results->isNotEmpty())
        @foreach($results->groupBy('type') as $type => $group)
            <h2 class="fw-bold mt-3 mb-2" style="font-size:1rem;color:var(--hb-gray-900);">{{ $type }}{{ $group->count() > 1 ? 's' : '' }}</h2>
            @foreach($group as $result)
                <div class="card mb-2" style="border-radius:0.75rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-3">
                        <a href="{{ $result['url'] }}" class="hb-link fw-bold" style="font-size:0.9rem;">{{ $result['title'] }}</a>
                        <p style="font-size:0.8rem;color:var(--hb-gray-600);margin-bottom:0;">{{ $result['excerpt'] }}</p>
                    </div>
                </div>
            @endforeach
        @endforeach
    @endif
</x-public-layout>
