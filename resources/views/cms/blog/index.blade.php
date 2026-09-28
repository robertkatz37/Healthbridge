<x-public-layout title="Blog | HealthsBridge" seo-description="Caregiving tips, senior living guides, and advice from the HealthsBridge team." :breadcrumb-items="$breadcrumbs->items()">
    <div class="row g-4">
        <div class="col-lg-8">
            @if($featuredPosts->isNotEmpty() && !request()->hasAny(['category', 'tag', 'q']))
                <h2 class="fw-bold mb-3" style="font-family:'Fraunces',serif;color:var(--hb-gray-900);">Featured</h2>
                <div class="row g-3 mb-4">
                    @foreach($featuredPosts as $post)
                        <div class="col-md-4">
                            <a href="{{ route('blog.show', $post) }}" class="text-decoration-none">
                                <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                                    @if($post->featured_image)
                                        <img src="{{ asset('storage/' . $post->featured_image) }}" class="card-img-top" style="height:140px;object-fit:cover;border-radius:1rem 1rem 0 0;">
                                    @endif
                                    <div class="card-body p-3">
                                        <span class="hb-badge-verified" style="font-size:0.65rem;">{{ $post->category->name }}</span>
                                        <h3 class="fw-bold mt-2" style="font-size:0.95rem;color:var(--hb-gray-900);">{{ $post->title }}</h3>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="GET" class="mb-4">
                <input type="search" name="q" class="hb-form-control" placeholder="Search articles..." value="{{ request('q') }}">
            </form>

            @if($posts->isEmpty())
                <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body text-center py-5">
                        <p style="color:var(--hb-gray-600);margin:0;">No articles found.</p>
                    </div>
                </div>
            @else
                @foreach($posts as $post)
                    <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="hb-badge-verified">{{ $post->category->name }}</span>
                                <span style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $post->reading_time_minutes }} min read</span>
                            </div>
                            <a href="{{ route('blog.show', $post) }}" class="text-decoration-none">
                                <h2 class="fw-bold mt-2" style="font-size:1.1rem;color:var(--hb-gray-900);">{{ $post->title }}</h2>
                            </a>
                            <p style="font-size:0.875rem;color:var(--hb-gray-600);">{{ $post->excerpt ?? Str::limit(strip_tags($post->body), 150) }}</p>
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);">By {{ $post->author->name }} &middot; {{ $post->published_at?->format('M d, Y') }}</div>
                        </div>
                    </div>
                @endforeach
                {{ $posts->links() }}
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h3 class="fw-bold mb-3" style="font-size:1rem;color:var(--hb-gray-900);">Categories</h3>
                    @foreach($categories as $category)
                        <a href="{{ route('blog.index', ['category' => $category->slug]) }}" class="d-flex justify-content-between text-decoration-none py-1">
                            <span style="color:var(--hb-gray-900);font-size:0.875rem;">{{ $category->name }}</span>
                            <span style="color:var(--hb-gray-600);font-size:0.8rem;">{{ $category->posts_count }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h3 class="fw-bold mb-3" style="font-size:1rem;color:var(--hb-gray-900);">Tags</h3>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($tags as $tag)
                            <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}" class="hb-badge-verified" style="text-decoration:none;">{{ $tag->name }} ({{ $tag->posts_count }})</a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-public-layout>
