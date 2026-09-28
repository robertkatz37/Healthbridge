<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Advisor\StoreMessageRequest;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Family Communication History — reuses the polymorphic conversations/
 * messages tables (Phase 2, deferred UI until now) attached to a Lead via
 * Lead::conversations() morphMany.
 */
class ConversationController extends Controller
{
    public function show(Request $request, Lead $lead): View
    {
        $this->authorize('view', $lead);

        $conversation = $lead->conversations()->with('messages.sender')->first()
            ?? $lead->conversations()->create();

        return view('advisor.leads.conversation', compact('lead', 'conversation'));
    }

    public function store(StoreMessageRequest $request, Lead $lead): RedirectResponse
    {
        $conversation = $lead->conversations()->first() ?? $lead->conversations()->create();

        $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        activity()->causedBy($request->user())->performedOn($lead)->log('Message sent to family');

        return back()->with('status', 'message-sent');
    }
}
