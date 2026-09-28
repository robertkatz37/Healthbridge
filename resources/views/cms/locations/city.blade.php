<x-public-layout
    :title="$seoTitle"
    :seo-description="$seoDescription"
    :canonical-url="$guide?->seoMeta?->canonical_url ?? $canonicalUrl"
    :robots="$robots"
    :json-ld="[$faqSchema, $breadcrumbSchema]"
    :breadcrumb-items="$breadcrumbs->items()"
>
    <h1 class="fw-bold mb-2" style="font-family:'Fraunces',serif;color:var(--hb-gray-900);">Senior Care Agencies in {{ $city->name }}, {{ $state->code }}</h1>

    @if($guide?->status === 'published' && $guide->intro_content)
        <div style="color:var(--hb-gray-900);line-height:1.7;" class="mb-4">{!! $guide->intro_content !!}</div>
    @else
        <p style="color:var(--hb-gray-600);" class="mb-4">Browse verified senior care agencies in {{ $city->name }}, {{ $state->name }}.</p>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <h2 class="fw-bold mb-3" style="font-size:1.1rem;color:var(--hb-gray-900);">Agencies</h2>
            @if($agencies->isEmpty())
                <p style="color:var(--hb-gray-600);">No published agencies in {{ $city->name }} yet — try a nearby city.</p>
            @else
                <div class="row g-3">
                    @foreach($agencies as $agency)
                        <div class="col-md-6">
                            <a href="{{ route('agencies.show', $agency) }}" class="text-decoration-none">
                                <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                                    <div class="card-body p-3">
                                        <div style="font-weight:600;color:var(--hb-gray-900);font-size:0.9rem;">{{ $agency->name }}</div>
                                        <div style="font-size:0.8rem;color:var(--hb-gray-600);">{{ $agency->category->name }}</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
                {{ $agencies->links() }}
            @endif

            @if($faqs->isNotEmpty())
                <h2 class="fw-bold mt-4 mb-3" style="font-size:1.1rem;color:var(--hb-gray-900);">Frequently Asked Questions</h2>
                <div class="accordion" id="cityFaqAccordion">
                    @foreach($faqs as $faq)
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cityFaq{{ $faq->id }}">{{ $faq->question }}</button>
                            </h3>
                            <div id="cityFaq{{ $faq->id }}" class="accordion-collapse collapse" data-bs-parent="#cityFaqAccordion">
                                <div class="accordion-body" style="font-size:0.9rem;color:var(--hb-gray-600);">{{ $faq->answer }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            @if($nearbyCities->isNotEmpty())
                <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h3 class="fw-bold mb-3" style="font-size:1rem;color:var(--hb-gray-900);">Nearby Cities</h3>
                        @foreach($nearbyCities as $nearby)
                            <a href="{{ url('/' . $state->slug . '/' . $nearby->clean_slug) }}" class="d-block text-decoration-none py-1" style="color:var(--hb-gray-900);font-size:0.875rem;">{{ $nearby->name }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
            @if($relatedServices->isNotEmpty())
                <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h3 class="fw-bold mb-3" style="font-size:1rem;color:var(--hb-gray-900);">Related Services</h3>
                        @foreach($relatedServices as $service)
                            <a href="{{ url('/' . $service->slug) }}" class="d-block text-decoration-none py-1" style="color:var(--hb-gray-900);font-size:0.875rem;">{{ $service->name }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-public-layout>
