<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\UpdateBrandingSettingsRequest;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BrandingSettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function edit(Request $request): View
    {
        $this->authorize('manage-platform-settings');

        $values = $this->settings->getGroup('branding');

        return view('admin.settings.branding', compact('values'));
    }

    public function update(UpdateBrandingSettingsRequest $request): RedirectResponse
    {
        $this->settings->set('branding_company_name', $request->branding_company_name, 'branding', 'string');
        $this->settings->set('branding_from_name', $request->branding_from_name, 'branding', 'string');
        $this->settings->set('branding_from_email', $request->branding_from_email, 'branding', 'string');

        if ($request->hasFile('logo')) {
            $oldPath = $this->settings->getRaw('branding_logo_path');
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('logo')->store('branding', 'public');
            $this->settings->set('branding_logo_path', $path, 'branding', 'string');
        }

        activity()->causedBy($request->user())->log('Branding settings updated');

        return back()->with('status', 'settings-updated');
    }
}
