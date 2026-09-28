<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class FamilyLayout extends Component
{
    public function __construct(
        public string $title = 'Family Dashboard',
        public ?string $breadcrumb = null,
    ) {}

    public function render(): View
    {
        return view('layouts.family');
    }
}
