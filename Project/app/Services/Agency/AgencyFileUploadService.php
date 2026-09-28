<?php

namespace App\Services\Agency;

use App\Models\Agency;
use App\Models\AgencyDocument;
use App\Models\AgencyMedia;
use Illuminate\Http\UploadedFile;

/**
 * Centralizes agency file upload handling so validation/storage rules live
 * in one place, per CODING_STANDARDS.md (no duplicated code across
 * controllers). Two distinct storage strategies, per SRS NFR-1 / FR-11:
 *
 *  - Public media (photos/videos for the marketplace listing) → 'public'
 *    disk, directly browser-accessible via the storage symlink.
 *  - Private documents (licenses/insurance) → 'local' disk (outside the
 *    web root), never directly browser-accessible — served only via a
 *    signed, temporary URL through AgencyDocumentController::download().
 */
class AgencyFileUploadService
{
    private const ALLOWED_IMAGE_MIMES = ['jpg', 'jpeg', 'png', 'webp'];
    private const ALLOWED_DOCUMENT_MIMES = ['pdf', 'jpg', 'jpeg', 'png'];
    private const MAX_IMAGE_KB = 4096;
    private const MAX_DOCUMENT_KB = 8192;

    public function storeMedia(Agency $agency, UploadedFile $file, string $type, ?string $caption = null): AgencyMedia
    {
        $path = $file->store("agencies/{$agency->id}/media", 'public');

        $nextOrder = (int) $agency->media()->max('sort_order') + 1;

        return $agency->media()->create([
            'type' => $type,
            'path' => $path,
            'caption' => $caption,
            'sort_order' => $nextOrder,
        ]);
    }

    public function deleteMedia(AgencyMedia $media): void
    {
        \Illuminate\Support\Facades\Storage::disk('public')->delete($media->path);
        $media->delete();
    }

    public function storeDocument(
        Agency $agency,
        UploadedFile $file,
        string $documentType,
        ?string $expiresAt = null
    ): AgencyDocument {
        $path = $file->store("agencies/{$agency->id}/documents", 'local');

        return $agency->documents()->create([
            'document_type' => $documentType,
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'expires_at' => $expiresAt,
        ]);
    }

    public function deleteDocument(AgencyDocument $document): void
    {
        \Illuminate\Support\Facades\Storage::disk('local')->delete($document->path);
        $document->delete();
    }

    public static function imageValidationRules(): array
    {
        return [
            'required', 'file',
            'mimes:' . implode(',', self::ALLOWED_IMAGE_MIMES),
            'max:' . self::MAX_IMAGE_KB,
        ];
    }

    public static function documentValidationRules(): array
    {
        return [
            'required', 'file',
            'mimes:' . implode(',', self::ALLOWED_DOCUMENT_MIMES),
            'max:' . self::MAX_DOCUMENT_KB,
        ];
    }
}
