<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class PublicLayout extends Component
{
    public function __construct(
        public string $title = '',
        public ?string $seoDescription = null,
        public ?string $canonicalUrl = null,
        public ?string $ogImage = null,
        public string $robots = 'index,follow',
        public array $jsonLd = [],
        public array $breadcrumbItems = [],
    ) {}

    public function render(): View
    {
        return view('layouts.public');
    }
}
