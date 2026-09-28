<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-4">
                <label class="hb-form-label">Title</label>
                <input type="text" name="title" class="hb-form-control mb-3 @error('title') is-invalid @enderror" value="{{ old('title', $page?->title) }}" required>
                @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

                <label class="hb-form-label">Slug (optional — auto-generated from title if left blank)</label>
                <input type="text" name="slug" class="hb-form-control mb-3 @error('slug') is-invalid @enderror" value="{{ old('slug', $page?->slug) }}" placeholder="e.g. about-us">
                @error('slug')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

                <label class="hb-form-label">Body (used when no Page Builder sections are added below)</label>
                <textarea name="body" class="hb-form-control mb-3" rows="8">{{ old('body', $page?->body) }}</textarea>
            </div>
        </div>

        <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Page Builder Sections</h6>
                    <select id="addSectionType" class="form-select form-select-sm" style="width:auto;">
                        <option value="hero">Hero</option>
                        <option value="text">Text</option>
                        <option value="image">Image</option>
                        <option value="cta">Call to Action</option>
                        <option value="faq">FAQ</option>
                        <option value="features">Features</option>
                        <option value="testimonials">Testimonials</option>
                        <option value="html">Custom HTML</option>
                    </select>
                    <button type="button" id="addSectionBtn" class="btn btn-sm btn-primary" style="border-radius:0.5rem;">Add Section</button>
                </div>
                <div id="sectionsContainer">
                    @foreach(old('sections', $page?->sections->map(fn ($s) => ['type' => $s->type, 'content' => $s->content])->toArray() ?? []) as $index => $section)
                        <div class="section-block card mb-2" style="border-radius:0.75rem;">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="hb-badge-verified">{{ ucfirst($section['type']) }}</span>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-section" style="border-radius:0.5rem;">Remove</button>
                                </div>
                                <input type="hidden" name="sections[{{ $index }}][type]" value="{{ $section['type'] }}">
                                <textarea name="sections[{{ $index }}][content][json]" class="hb-form-control section-json" rows="3" placeholder='{"heading": "..."}'>{{ json_encode($section['content'] ?? []) }}</textarea>
                                <small style="font-size:0.7rem;color:var(--hb-gray-600);">Raw JSON content for this section (heading, subheading, body, image_path, button_text, button_url, items, etc. depending on type).</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">SEO</h6>
                <label class="hb-form-label">Meta Title</label>
                <input type="text" name="meta_title" class="hb-form-control mb-3" value="{{ old('meta_title', $page?->seoMeta?->meta_title) }}">
                <label class="hb-form-label">Meta Description</label>
                <textarea name="meta_description" class="hb-form-control mb-3" rows="2">{{ old('meta_description', $page?->seoMeta?->meta_description) }}</textarea>
                <label class="hb-form-label">OG Image Path</label>
                <input type="text" name="og_image" class="hb-form-control mb-3" value="{{ old('og_image', $page?->seoMeta?->og_image) }}">
                <label class="hb-form-label">Canonical URL</label>
                <input type="url" name="canonical_url" class="hb-form-control mb-3" value="{{ old('canonical_url', $page?->seoMeta?->canonical_url) }}">
                <label class="hb-form-label">Robots</label>
                <select name="robots" class="hb-form-control">
                    <option value="index,follow" {{ old('robots', $page?->seoMeta?->robots) === 'index,follow' ? 'selected' : '' }}>index, follow</option>
                    <option value="noindex,follow" {{ old('robots', $page?->seoMeta?->robots) === 'noindex,follow' ? 'selected' : '' }}>noindex, follow</option>
                    <option value="noindex,nofollow" {{ old('robots', $page?->seoMeta?->robots) === 'noindex,nofollow' ? 'selected' : '' }}>noindex, nofollow</option>
                </select>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-4">
                <label class="hb-form-label">Page Type</label>
                <select name="page_type" class="hb-form-control mb-3">
                    @foreach(['home' => 'Home', 'about' => 'About', 'contact' => 'Contact', 'privacy' => 'Privacy', 'terms' => 'Terms', 'careers' => 'Careers', 'custom' => 'Custom'] as $value => $label)
                        <option value="{{ $value }}" {{ old('page_type', $page?->page_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <label class="hb-form-label">Template</label>
                <input type="text" name="template" class="hb-form-control mb-3" value="{{ old('template', $page?->template ?? 'default') }}">

                <label class="hb-form-label">Status</label>
                <select name="status" class="hb-form-control mb-3">
                    @foreach(['draft' => 'Draft', 'published' => 'Published', 'scheduled' => 'Scheduled', 'archived' => 'Archived'] as $value => $label)
                        <option value="{{ $value }}" {{ old('status', $page?->status ?? 'draft') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-primary w-100" style="border-radius:0.625rem;">{{ $page ? 'Save Changes' : 'Create Page' }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let sectionIndex = {{ old('sections', $page?->sections->count() ?? 0) ? count(old('sections', $page?->sections->toArray() ?? [])) : ($page?->sections->count() ?? 0) }};
    document.getElementById('addSectionBtn').addEventListener('click', function () {
        const type = document.getElementById('addSectionType').value;
        const container = document.getElementById('sectionsContainer');
        const div = document.createElement('div');
        div.className = 'section-block card mb-2';
        div.style.borderRadius = '0.75rem';
        div.innerHTML = `
            <div class="card-body p-3">
                <div class="d-flex justify-content-between mb-2">
                    <span class="hb-badge-verified">${type.charAt(0).toUpperCase() + type.slice(1)}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-section" style="border-radius:0.5rem;">Remove</button>
                </div>
                <input type="hidden" name="sections[${sectionIndex}][type]" value="${type}">
                <textarea name="sections[${sectionIndex}][content][json]" class="hb-form-control section-json" rows="3" placeholder='{"heading": "..."}'>{}</textarea>
                <small style="font-size:0.7rem;color:var(--hb-gray-600);">Raw JSON content for this section.</small>
            </div>`;
        container.appendChild(div);
        sectionIndex++;
        attachRemoveHandlers();
    });

    function attachRemoveHandlers() {
        document.querySelectorAll('.remove-section').forEach(function (btn) {
            btn.onclick = function () { btn.closest('.section-block').remove(); };
        });
    }
    attachRemoveHandlers();

    document.querySelector('form').addEventListener('submit', function () {
        document.querySelectorAll('.section-json').forEach(function (textarea) {
            try {
                const parsed = JSON.parse(textarea.value || '{}');
                const name = textarea.name.replace('[content][json]', '[content]');
                Object.keys(parsed).forEach(function (key) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = name + '[' + key + ']';
                    hidden.value = typeof parsed[key] === 'object' ? JSON.stringify(parsed[key]) : parsed[key];
                    textarea.closest('.section-block').appendChild(hidden);
                });
                textarea.disabled = true;
            } catch (e) { /* invalid JSON - leave as-is, backend will skip malformed content */ }
        });
    });
</script>
@endpush
