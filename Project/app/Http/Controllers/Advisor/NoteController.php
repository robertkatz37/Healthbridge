<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Models\Advisor;
use App\Models\AdvisorNote;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dedicated cross-lead Notes page — the notes/comments themselves are
 * still created from within a Lead's detail page (LeadNoteController),
 * this is a read view aggregating every note this advisor has written
 * across every lead, matching the "Notes: Dedicated Notes page"
 * completion item.
 */
class NoteController extends Controller
{
    public function index(Request $request): View
    {
        $advisor = Advisor::where('user_id', $request->user()->id)->firstOrFail();
        $this->authorize('view', $advisor);

        $query = $advisor->notes()->with('lead.family.user')->latest();

        if ($request->input('filter') === 'internal') {
            $query->internal();
        } elseif ($request->input('filter') === 'notes') {
            $query->visible();
        }

        $notes = $query->paginate(20)->withQueryString();

        return view('advisor.notes.index', compact('notes'));
    }
}
