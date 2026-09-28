<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Family;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $family = Family::where('user_id', $request->user()->id)->firstOrFail();

        $favorites = $family->favorites()->with('agency.category')->latest()->get();

        return view('family.favorites.index', compact('favorites'));
    }

    /**
     * Toggles a favorite on/off — callable both from the public agency
     * show page and from the favorites list itself, so a family can
     * save/unsave an agency while browsing without a separate UI flow.
     */
    public function toggle(Request $request, Agency $agency): RedirectResponse
    {
        $family = Family::where('user_id', $request->user()->id)->firstOrFail();

        $existing = $family->favorites()->where('agency_id', $agency->id)->first();

        if ($existing) {
            $existing->delete();
            $status = 'favorite-removed';
        } else {
            $family->favorites()->create(['agency_id' => $agency->id]);
            $status = 'favorite-added';
        }

        activity()->causedBy($request->user())->performedOn($agency)->log(
            $existing ? 'Agency removed from favorites' : 'Agency saved to favorites'
        );

        return back()->with('status', $status);
    }
}
