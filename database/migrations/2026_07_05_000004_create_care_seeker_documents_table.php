<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mirrors agency_documents' private-disk pattern (Phase 7): stored
        // on the 'local' disk outside the web root, never a direct public
        // URL, served only via an authorized, ownership-checked download
        // route — appropriate given these may be medical records or POA
        // documents.
        Schema::create('care_seeker_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('care_seeker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->enum('document_type', ['medical_record', 'insurance_card', 'poa_document', 'other']);
            $table->string('path');
            $table->string('original_filename')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('care_seeker_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_seeker_documents');
    }
};
