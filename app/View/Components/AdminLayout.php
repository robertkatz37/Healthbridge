<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AdminLayout extends Component
{
    public function __construct(
        public string $title = 'Admin Panel',
        public ?string $breadcrumb = null,
    ) {}

    public function render(): View
    {
        return view('layouts.admin');
    }
}
