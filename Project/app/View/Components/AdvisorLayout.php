<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AdvisorLayout extends Component
{
    public function __construct(
        public string $title = 'Advisor Dashboard',
        public ?string $breadcrumb = null,
    ) {}

    public function render(): View
    {
        return view('layouts.advisor');
    }
}
