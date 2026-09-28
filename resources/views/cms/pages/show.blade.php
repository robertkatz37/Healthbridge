<x-public-layout
    :title="$seoTitle"
    :seo-description="$seoDescription"
    :og-image="$page->seoMeta?->og_image"
    :canonical-url="$canonicalUrl"
    :robots="$robots"
    :json-ld="[$page->seoMeta?->json_ld, $faqSchema, $breadcrumbSchema]"
    :breadcrumb-items="$breadcrumbs->items()"
>
    @if($page->activeSections->isNotEmpty())
        @foreach($page->activeSections as $section)
            @include('cms.pages.sections.' . $section->type, ['content' => $section->content, 'sectionId' => $section->id])
        @endforeach
    @else
        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-5">
                <h1 class="fw-bold mb-4" style="font-family:'Fraunces',serif;color:var(--hb-gray-900);">{{ $page->title }}</h1>
                <div style="color:var(--hb-gray-900);line-height:1.7;">{!! $page->body !!}</div>
            </div>
        </div>
    @endif

    @if($page->faqs->isNotEmpty())
        <div class="card mt-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-4">
                <h2 class="fw-bold mb-3" style="font-size:1.25rem;color:var(--hb-gray-900);">Frequently Asked Questions</h2>
                <div class="accordion" id="pageFaqAccordion">
                    @foreach($page->faqs as $faq)
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq{{ $faq->id }}">
                                    {{ $faq->question }}
                                </button>
                            </h3>
                            <div id="faq{{ $faq->id }}" class="accordion-collapse collapse" data-bs-parent="#pageFaqAccordion">
                                <div class="accordion-body" style="font-size:0.9rem;color:var(--hb-gray-600);">{{ $faq->answer }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</x-public-layout>
