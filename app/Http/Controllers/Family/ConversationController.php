<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Http\Requests\Family\StoreMessageRequest;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Family-side counterpart to Advisor\ConversationController (Phase 11) —
 * the advisor side of this conversation UI existed for two phases with
 * no way for a family to actually reply, a real gap surfaced while
 * building the Family Dashboard's "Advisor Messages" widget (Phase 13).
 * Reuses the exact same conversations/messages tables and Lead
 * relationship; only the authorization (LeadPolicy::message(), family-
 * scoped) and layout differ.
 */
class ConversationController extends Controller
{
    public function show(Request $request, Lead $lead): View
    {
        $this->authorize('message', $lead);

        $conversation = $lead->conversations()->with('messages.sender')->first()
            ?? $lead->conversations()->create();

        return view('family.conversation', compact('lead', 'conversation'));
    }

    public function store(StoreMessageRequest $request, Lead $lead): RedirectResponse
    {
        $conversation = $lead->conversations()->first() ?? $lead->conversations()->create();

        $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        activity()->causedBy($request->user())->performedOn($lead)->log('Message sent to advisor');

        return back()->with('status', 'message-sent');
    }
}
