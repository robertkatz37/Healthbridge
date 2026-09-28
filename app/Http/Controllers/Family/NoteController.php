<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Http\Requests\Family\StoreFamilyNoteRequest;
use App\Models\Family;
use App\Models\FamilyNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NoteController extends Controller
{
    public function index(Request $request): View
    {
        $family = Family::where('user_id', $request->user()->id)->firstOrFail();
        $this->authorize('viewAny', FamilyNote::class);

        $notes = $family->notes()->with('careSeeker')->latest()->get();
        $careSeekers = $family->careSeekers;

        return view('family.notes.index', compact('notes', 'careSeekers'));
    }

    public function store(StoreFamilyNoteRequest $request): RedirectResponse
    {
        $family = Family::where('user_id', $request->user()->id)->firstOrFail();

        $family->notes()->create($request->validated());

        activity()->causedBy($request->user())->performedOn($family)->log('Family note added');

        return back()->with('status', 'note-added');
    }

    public function destroy(Request $request, FamilyNote $note): RedirectResponse
    {
        $this->authorize('delete', $note);

        $note->delete();

        return back()->with('status', 'note-deleted');
    }
}
