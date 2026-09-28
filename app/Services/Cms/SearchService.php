<?php

namespace App\Services\Cms;

use App\Models\Agency;
use App\Models\BlogPost;
use App\Models\City;
use App\Models\CmsPage;
use App\Models\Faq;
use Illuminate\Support\Collection;

class SearchService
{
    public function search(string $query, int $limitPerType = 5): Collection
    {
        $term = '%' . $query . '%';
        $results = collect();

        CmsPage::visible()->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('body', 'like', $term))
            ->limit($limitPerType)->get()->each(function (CmsPage $page) use ($results) {
                $results->push([
                    'type' => 'Page',
                    'title' => $page->title,
                    'url' => $page->page_type === 'home' ? url('/') : url('/' . $page->slug),
                    'excerpt' => str(strip_tags($page->body))->limit(150)->toString(),
                ]);
            });

        BlogPost::visible()->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('body', 'like', $term)->orWhere('excerpt', 'like', $term))
            ->limit($limitPerType)->get()->each(function (BlogPost $post) use ($results) {
                $results->push([
                    'type' => 'Blog Post',
                    'title' => $post->title,
                    'url' => route('blog.show', $post),
                    'excerpt' => $post->excerpt ?? str(strip_tags($post->body))->limit(150)->toString(),
                ]);
            });

        Agency::published()->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('city', 'like', $term)->orWhere('description', 'like', $term))
            ->limit($limitPerType)->get()->each(function (Agency $agency) use ($results) {
                $results->push([
                    'type' => 'Agency',
                    'title' => $agency->name,
                    'url' => route('agencies.show', $agency),
                    'excerpt' => $agency->city . ', ' . $agency->state,
                ]);
            });

        City::active()->where('name', 'like', $term)->with('state')
            ->limit($limitPerType)->get()->each(function (City $city) use ($results) {
                $results->push([
                    'type' => 'City',
                    'title' => $city->name . ', ' . $city->state->code,
                    'url' => url('/' . $city->state->slug . '/' . $city->clean_slug),
                    'excerpt' => 'Senior care agencies in ' . $city->name . ', ' . $city->state->name,
                ]);
            });

        Faq::where(fn ($q) => $q->where('question', 'like', $term)->orWhere('answer', 'like', $term))
            ->limit($limitPerType)->get()->each(function (Faq $faq) use ($results) {
                $results->push([
                    'type' => 'FAQ',
                    'title' => $faq->question,
                    'url' => '#',
                    'excerpt' => str(strip_tags($faq->answer))->limit(150)->toString(),
                ]);
            });

        return $results;
    }
}
