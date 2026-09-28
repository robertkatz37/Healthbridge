<?php

namespace App\Services\Cms;

class BreadcrumbService
{
    private array $items = [];

    public static function make(): self
    {
        return new self();
    }

    public function add(string $label, ?string $url = null): self
    {
        $this->items[] = ['label' => $label, 'url' => $url];

        return $this;
    }

    public function items(): array
    {
        return $this->items;
    }

    public function schema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($this->items)->values()->map(fn ($item, $index) => array_filter([
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['label'],
                'item' => $item['url'] ? url($item['url']) : null,
            ]))->all(),
        ];
    }
}
