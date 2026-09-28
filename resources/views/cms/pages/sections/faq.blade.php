<div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
    <div class="card-body p-4">
        @if(!empty($content['heading']))
            <h2 class="fw-bold mb-3" style="font-size:1.25rem;color:var(--hb-gray-900);">{{ $content['heading'] }}</h2>
        @endif
        <div class="accordion" id="sectionFaqAccordion{{ $sectionId }}">
            @foreach(($content['items'] ?? []) as $index => $item)
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sectionFaq{{ $sectionId }}-{{ $index }}">
                            {{ $item['question'] ?? '' }}
                        </button>
                    </h3>
                    <div id="sectionFaq{{ $sectionId }}-{{ $index }}" class="accordion-collapse collapse" data-bs-parent="#sectionFaqAccordion{{ $sectionId }}">
                        <div class="accordion-body" style="font-size:0.9rem;color:var(--hb-gray-600);">{{ $item['answer'] ?? '' }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
