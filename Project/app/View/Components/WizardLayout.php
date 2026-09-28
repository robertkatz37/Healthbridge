<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class WizardLayout extends Component
{
    public function __construct(
        public string $title = 'Agency Onboarding',
        public int $currentStep = 1,
    ) {}

    public function render(): View
    {
        return view('layouts.wizard');
    }
}
