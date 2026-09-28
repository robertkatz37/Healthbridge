<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NeedsAssessmentAnswer extends Model
{
    protected $fillable = ['needs_assessment_id', 'question_id', 'answer_value'];

    protected function casts(): array
    {
        return ['answer_value' => 'array'];
    }

    public function needsAssessment(): BelongsTo
    {
        return $this->belongsTo(NeedsAssessment::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(NeedsAssessmentQuestion::class, 'question_id');
    }
}
