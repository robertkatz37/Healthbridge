<?php

namespace App\Services\Advisor;

use App\Models\Advisor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Mirrors CareSeekerFileUploadService's (Phase 9) public-disk photo
 * pattern.
 */
class AdvisorFileUploadService
{
    public function storePhoto(Advisor $advisor, UploadedFile $file): void
    {
        if ($advisor->photo_path) {
            Storage::disk('public')->delete($advisor->photo_path);
        }

        $path = $file->store("advisors/{$advisor->id}/photo", 'public');
        $advisor->update(['photo_path' => $path]);
    }

    public function deletePhoto(Advisor $advisor): void
    {
        if ($advisor->photo_path) {
            Storage::disk('public')->delete($advisor->photo_path);
            $advisor->update(['photo_path' => null]);
        }
    }
}
