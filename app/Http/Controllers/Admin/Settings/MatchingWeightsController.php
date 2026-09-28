<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\UpdateMatchingWeightsRequest;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Store configurable matching weights in the database... so Super Admin
 * can adjust them without changing code" — reuses the exact Phase 10
 * Settings architecture (SettingsService, group 'matching') rather than
 * building a parallel configuration mechanism.
 */
class MatchingWeightsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function edit(Request $request): View
    {
        $this->authorize('manage-platform-settings');

        $weights = $this->settings->get('matching_weights', []);
        $featuredBoost = $this->settings->get('matching_featured_boost', 3);
        $maxDistance = $this->settings->get('matching_max_distance_miles', 100);
        $minScoreThreshold = $this->settings->get('matching_min_score_threshold', 30);

        return view('admin.settings.matching-weights', compact('weights', 'featuredBoost', 'maxDistance', 'minScoreThreshold'));
    }

    public function update(UpdateMatchingWeightsRequest $request): RedirectResponse
    {
        $this->settings->set('matching_weights', $request->input('weights'), 'matching', 'json');
        $this->settings->set('matching_featured_boost', $request->featured_boost, 'matching', 'int');
        $this->settings->set('matching_max_distance_miles', $request->max_distance_miles, 'matching', 'int');
        $this->settings->set('matching_min_score_threshold', $request->min_score_threshold, 'matching', 'int');

        activity()->causedBy($request->user())->log('Matching engine weights updated');

        return back()->with('status', 'settings-updated');
    }
}
