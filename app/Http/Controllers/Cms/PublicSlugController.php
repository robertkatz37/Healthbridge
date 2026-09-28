<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\AgencyCategory;
use App\Models\State;
use Illuminate\View\View;

/**
 * Clean, unprefixed public URLs (/california, /home-care, /about) all
 * share the same one-segment shape, and Laravel resolves ambiguous
 * routes by registration order, not by whether the bound model
 * actually exists — three separate implicit-binding routes for the
 * same shape would always hit the first one and 404 immediately
 * rather than falling through to the next. This controller does the
 * State -> Service -> CmsPage disambiguation explicitly in one place
 * instead, delegating to each controller's existing method once the
 * right type is identified. Same reasoning for the two-segment
 * State+City shape in double().
 */
class PublicSlugController extends Controller
{
    public function __construct(
        private readonly LocationController $locations,
        private readonly ServiceController $services,
        private readonly CmsPageController $pages,
    ) {}

    public function single(string $slug): View
    {
        // State and Service slugs are always a single segment — a slug
        // containing a slash can only be a nested CMS page, so skip
        // straight to that lookup rather than querying State/Service
        // with a value that could never match either.
        if (!str_contains($slug, '/')) {
            if ($state = State::where('slug', $slug)->active()->first()) {
                return $this->locations->state($state);
            }

            if ($category = AgencyCategory::where('slug', $slug)->active()->first()) {
                return $this->services->show($category);
            }
        }

        return $this->pages->show($slug);
    }

    public function double(string $first, string $second): View
    {
        if ($state = State::where('slug', $first)->active()->first()) {
            return $this->locations->city($state, $second);
        }

        // No other two-segment public entity exists yet — fall back to
        // a CMS page whose own slug happens to contain a slash
        // (e.g. an admin-created "legal/privacy"), rather than a bare 404.
        return $this->pages->show($first . '/' . $second);
    }
}
