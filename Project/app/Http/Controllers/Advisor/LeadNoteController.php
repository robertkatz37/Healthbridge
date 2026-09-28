<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Advisor\StoreLeadNoteRequest;
use App\Models\Advisor;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;

class LeadNoteController extends Controller
{
    public function store(StoreLeadNoteRequest $request, Lead $lead): RedirectResponse
    {
        $advisor = Advisor::where('user_id', $request->user()->id)->firstOrFail();

        $lead->notes()->create([
            'advisor_id' => $advisor->id,
            'family_id' => $lead->family_id,
            'note_type' => $request->note_type,
            'is_internal' => $request->boolean('is_internal'),
            'content' => $request->content,
        ]);

        activity()->causedBy($request->user())->performedOn($lead)->log(
            $request->boolean('is_internal') ? 'Internal comment added' : 'Lead note added'
        );

        return back()->with('status', 'note-added');
    }
}
