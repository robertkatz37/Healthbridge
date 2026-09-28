<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-4">
                <label class="hb-form-label">Title</label>
                <input type="text" name="title" class="hb-form-control mb-3 @error('title') is-invalid @enderror" value="{{ old('title', $post?->title) }}" required>
                @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

                <label class="hb-form-label">Slug (optional)</label>
                <input type="text" name="slug" class="hb-form-control mb-3" value="{{ old('slug', $post?->slug) }}">

                <label class="hb-form-label">Excerpt</label>
                <textarea name="excerpt" class="hb-form-control mb-3" rows="2">{{ old('excerpt', $post?->excerpt) }}</textarea>

                <label class="hb-form-label">Body</label>
                <textarea name="body" class="hb-form-control mb-3 @error('body') is-invalid @enderror" rows="12">{{ old('body', $post?->body) }}</textarea>
                @error('body')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">SEO</h6>
                <label class="hb-form-label">Meta Title</label>
                <input type="text" name="meta_title" class="hb-form-control mb-3" value="{{ old('meta_title', $post?->seoMeta?->meta_title) }}">
                <label class="hb-form-label">Meta Description</label>
                <textarea name="meta_description" class="hb-form-control" rows="2">{{ old('meta_description', $post?->seoMeta?->meta_description) }}</textarea>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-4">
                <label class="hb-form-label">Category</label>
                <select name="blog_category_id" class="hb-form-control mb-3" required>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('blog_category_id', $post?->blog_category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>

                <label class="hb-form-label">Tags</label>
                <select name="tag_ids[]" class="hb-form-control mb-3" multiple>
                    @foreach($tags as $tag)
                        <option value="{{ $tag->id }}" {{ in_array($tag->id, old('tag_ids', $post?->tags->pluck('id')->toArray() ?? [])) ? 'selected' : '' }}>{{ $tag->name }}</option>
                    @endforeach
                </select>

                <label class="hb-form-label">Featured Image</label>
                <input type="file" name="featured_image" class="hb-form-control mb-3" accept="image/*">
                @if($post?->featured_image)
                    <img src="{{ asset('storage/' . $post->featured_image) }}" style="width:100%;border-radius:0.5rem;margin-bottom:0.75rem;">
                @endif

                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" name="is_featured" value="1" id="isFeatured" {{ old('is_featured', $post?->is_featured) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isFeatured">Featured Post</label>
                </div>

                <label class="hb-form-label">Status</label>
                <select name="status" class="hb-form-control mb-3">
                    @foreach(['draft' => 'Draft', 'scheduled' => 'Scheduled', 'published' => 'Published'] as $value => $label)
                        <option value="{{ $value }}" {{ old('status', $post?->status ?? 'draft') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <label class="hb-form-label">Publish/Schedule At</label>
                <input type="datetime-local" name="published_at" class="hb-form-control mb-3" value="{{ old('published_at', $post?->published_at?->format('Y-m-d\TH:i')) }}">

                <button type="submit" class="btn btn-primary w-100" style="border-radius:0.625rem;">{{ $post ? 'Save Changes' : 'Create Post' }}</button>
            </div>
        </div>
    </div>
</div>
