<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\UpdateEmailTemplateRequest;
use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Templates are edit-only, not freely creatable — each `key` corresponds
 * to an actual call site in code (RendersFromEmailTemplate), so there is
 * no "create new template" action; an admin customizes the wording of an
 * existing system email, they don't invent a new one with nothing behind
 * it. See EmailTemplateSeeder for the seeded set.
 */
class EmailTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage-platform-settings');

        $templates = EmailTemplate::orderBy('name')->get();

        return view('admin.settings.templates.index', compact('templates'));
    }

    public function edit(Request $request, EmailTemplate $template): View
    {
        $this->authorize('manage-platform-settings');

        return view('admin.settings.templates.edit', compact('template'));
    }

    public function update(UpdateEmailTemplateRequest $request, EmailTemplate $template): RedirectResponse
    {
        $template->update([
            'subject' => $request->subject,
            'body' => $request->body,
            'is_active' => $request->boolean('is_active'),
        ]);

        activity()->causedBy($request->user())->performedOn($template)->log('Email template updated');

        return redirect()->route('admin.settings.templates.index')->with('status', 'template-updated');
    }
}
