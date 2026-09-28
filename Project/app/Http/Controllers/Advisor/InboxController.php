<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Models\Advisor;
use App\Models\Conversation;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Communication Center — a unified inbox listing every conversation
 * thread across this advisor's leads, each with its most recent message
 * previewed, so an advisor doesn't have to open every lead individually
 * to see what needs a reply. The thread itself is still read/sent from
 * the per-lead conversation view (ConversationController) — this is the
 * index/triage screen.
 */
class InboxController extends Controller
{
    public function index(Request $request): View
    {
        $advisor = Advisor::where('user_id', $request->user()->id)->firstOrFail();
        $this->authorize('view', $advisor);

        $leadIds = Lead::where(function ($q) use ($advisor) {
            $q->where('advisor_id', $advisor->id);
            if ($advisor->teamMembers()->exists()) {
                $q->orWhereIn('advisor_id', $advisor->teamMembers()->pluck('id'));
            }
        })->pluck('id');

        $conversations = Conversation::where('subject_type', Lead::class)
            ->whereIn('subject_id', $leadIds)
            ->with(['subject.family.user', 'latestMessage.sender'])
            ->whereHas('messages')
            ->get()
            ->sortByDesc(fn (Conversation $c) => $c->latestMessage?->created_at)
            ->values();

        return view('advisor.inbox', compact('conversations'));
    }
}
