<?php

namespace App\Enums;

enum MediaType: string
{
    case Photo = 'photo';
    case Video = 'video';
    case VirtualTour = 'virtual_tour';

    public function label(): string
    {
        return match ($this) {
            self::Photo => 'Photo',
            self::Video => 'Video',
            self::VirtualTour => 'Virtual Tour',
        };
    }
}
