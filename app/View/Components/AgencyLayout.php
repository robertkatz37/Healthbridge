<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AgencyLayout extends Component
{
    public function __construct(
        public string $title = 'Agency Dashboard',
        public ?string $breadcrumb = null,
    ) {}

    public function render(): View
    {
        return view('layouts.agency');
    }
}
