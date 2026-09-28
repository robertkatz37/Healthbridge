<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\UpdateGeneralSettingsRequest;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GeneralSettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function edit(Request $request): View
    {
        $this->authorize('manage-platform-settings');

        $values = $this->settings->getGroup('general');

        return view('admin.settings.general', compact('values'));
    }

    public function update(UpdateGeneralSettingsRequest $request): RedirectResponse
    {
        $this->settings->set('platform_name', $request->platform_name, 'general', 'string');
        $this->settings->set('support_email', $request->support_email, 'general', 'string');
        $this->settings->set('support_phone', $request->support_phone ?? '', 'general', 'string');
        $this->settings->set('timezone', $request->timezone, 'general', 'string');
        $this->settings->set('maintenance_mode', $request->boolean('maintenance_mode') ? 'true' : 'false', 'general', 'bool');

        activity()->causedBy($request->user())->log('General settings updated');

        return back()->with('status', 'settings-updated');
    }
}
