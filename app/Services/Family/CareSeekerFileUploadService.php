<?php

namespace App\Services\Family;

use App\Models\CareSeeker;
use App\Models\CareSeekerDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Mirrors AgencyFileUploadService's public-vs-private disk split (Phase 7):
 * the care seeker's photo is public (used for display in the family UI,
 * and eventually visible to matched agencies), while documents (medical
 * records, insurance cards, POA) are private — stored outside the web
 * root, served only via an authorized, ownership-checked download route.
 */
class CareSeekerFileUploadService
{
    public function storePhoto(CareSeeker $careSeeker, UploadedFile $file): void
    {
        if ($careSeeker->photo_path) {
            Storage::disk('public')->delete($careSeeker->photo_path);
        }

        $path = $file->store("care-seekers/{$careSeeker->id}/photo", 'public');
        $careSeeker->update(['photo_path' => $path]);
    }

    public function deletePhoto(CareSeeker $careSeeker): void
    {
        if ($careSeeker->photo_path) {
            Storage::disk('public')->delete($careSeeker->photo_path);
            $careSeeker->update(['photo_path' => null]);
        }
    }

    public function storeDocument(
        CareSeeker $careSeeker,
        UploadedFile $file,
        string $documentType,
        User $uploader,
    ): CareSeekerDocument {
        $path = $file->store("care-seekers/{$careSeeker->id}/documents", 'local');

        return $careSeeker->documents()->create([
            'uploaded_by' => $uploader->id,
            'document_type' => $documentType,
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
        ]);
    }

    public function deleteDocument(CareSeekerDocument $document): void
    {
        Storage::disk('local')->delete($document->path);
        $document->delete();
    }
}
