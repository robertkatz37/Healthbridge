<x-public-layout
    :title="$seoTitle"
    :seo-description="$seoDescription"
    :og-image="$post->featured_image"
    :canonical-url="$post->seoMeta?->canonical_url"
    :robots="$post->seoMeta?->robots ?? 'index,follow'"
    :json-ld="[$articleSchema, $breadcrumbSchema]"
    :breadcrumb-items="$breadcrumbs->items()"
>
    <div class="row g-4">
        <div class="col-lg-8">
            <span class="hb-badge-verified">{{ $post->category->name }}</span>
            <h1 class="fw-bold mt-2 mb-2" style="font-family:'Fraunces',serif;color:var(--hb-gray-900);">{{ $post->title }}</h1>
            <div style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:1.5rem;">
                By {{ $post->author->name }} &middot; {{ $post->published_at?->format('M d, Y') }} &middot; {{ $post->reading_time_minutes }} min read
            </div>

            @if($post->featured_image)
                <img src="{{ asset('storage/' . $post->featured_image) }}" class="img-fluid mb-4" style="border-radius:1rem;width:100%;max-height:420px;object-fit:cover;">
            @endif

            <div style="color:var(--hb-gray-900);line-height:1.8;font-size:1rem;" class="mb-4">{!! $post->body !!}</div>

            @if($post->tags->isNotEmpty())
                <div class="d-flex flex-wrap gap-2 mb-4">
                    @foreach($post->tags as $tag)
                        <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}" class="hb-badge-verified" style="text-decoration:none;">{{ $tag->name }}</a>
                    @endforeach
                </div>
            @endif

            @if($commentsEnabled)
                <div class="card mt-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h2 class="fw-bold mb-3" style="font-size:1.1rem;color:var(--hb-gray-900);">Comments ({{ $post->comments->count() }})</h2>

                        @auth
                            <form method="POST" action="{{ route('blog.comments.store', $post) }}" class="mb-4">
                                @csrf
                                <textarea name="body" class="hb-form-control mb-2" rows="3" placeholder="Share your thoughts..." required></textarea>
                                <button type="submit" class="btn btn-primary btn-sm" style="border-radius:0.5rem;">Post Comment</button>
                            </form>
                        @else
                            <p style="font-size:0.85rem;color:var(--hb-gray-600);">
                                <a href="{{ route('login') }}" class="hb-link">Sign in</a> to leave a comment.
                            </p>
                        @endauth

                        @forelse($post->comments as $comment)
                            <div class="mb-3 pb-3" style="border-bottom:1px solid var(--hb-gray-200);">
                                <div style="font-size:0.8rem;font-weight:600;color:var(--hb-gray-900);">{{ $comment->user->name }}</div>
                                <div style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $comment->created_at->diffForHumans() }}</div>
                                <p style="font-size:0.875rem;color:var(--hb-gray-900);margin-top:0.35rem;">{{ $comment->body }}</p>
                            </div>
                        @empty
                            <p style="font-size:0.85rem;color:var(--hb-gray-600);">No comments yet — be the first to share your thoughts.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            @if($relatedPosts->isNotEmpty())
                <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h3 class="fw-bold mb-3" style="font-size:1rem;color:var(--hb-gray-900);">Related Articles</h3>
                        @foreach($relatedPosts as $related)
                            <a href="{{ route('blog.show', $related) }}" class="text-decoration-none">
                                <div class="mb-3 pb-3" style="border-bottom:1px solid var(--hb-gray-200);">
                                    <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">{{ $related->title }}</div>
                                    <div style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $related->reading_time_minutes }} min read</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-public-layout>
