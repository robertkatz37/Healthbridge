<?php

namespace App\Services\Cms;

use App\Models\Agency;
use App\Models\BlogPost;
use App\Models\CityGuide;
use App\Models\CmsPage;
use App\Models\ServiceGuide;
use App\Models\StateGuide;
use Illuminate\Support\Collection;

class SitemapService
{
    public function urls(): Collection
    {
        $urls = collect();

        $urls->push(['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'daily']);
        $urls->push(['loc' => route('agencies.index'), 'priority' => '0.9', 'changefreq' => 'daily']);
        $urls->push(['loc' => route('blog.index'), 'priority' => '0.8', 'changefreq' => 'daily']);

        foreach (CmsPage::visible()->get() as $page) {
            $urls->push([
                'loc' => $page->page_type === 'home' ? url('/') : url('/' . $page->slug),
                'lastmod' => $page->updated_at->toAtomString(),
                'priority' => '0.7',
                'changefreq' => 'weekly',
            ]);
        }

        foreach (BlogPost::visible()->get() as $post) {
            $urls->push([
                'loc' => route('blog.show', $post),
                'lastmod' => $post->updated_at->toAtomString(),
                'priority' => '0.6',
                'changefreq' => 'monthly',
            ]);
        }

        foreach (Agency::published()->get() as $agency) {
            $urls->push([
                'loc' => route('agencies.show', $agency),
                'lastmod' => $agency->updated_at->toAtomString(),
                'priority' => '0.8',
                'changefreq' => 'weekly',
            ]);
        }

        foreach (StateGuide::published()->with('state')->get() as $guide) {
            $urls->push(['loc' => url('/' . $guide->state->slug), 'priority' => '0.6', 'changefreq' => 'monthly']);
        }

        foreach (CityGuide::published()->with('city.state')->get() as $guide) {
            $urls->push(['loc' => url('/' . $guide->city->state->slug . '/' . $guide->city->clean_slug), 'priority' => '0.6', 'changefreq' => 'monthly']);
        }

        foreach (ServiceGuide::published()->with('category')->get() as $guide) {
            $urls->push(['loc' => url('/' . $guide->category->slug), 'priority' => '0.6', 'changefreq' => 'monthly']);
        }

        return $urls;
    }
}
