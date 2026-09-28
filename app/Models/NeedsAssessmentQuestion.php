<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NeedsAssessmentQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'section', 'section_order', 'question_text', 'input_type', 'options',
        'display_condition', 'weight', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'display_condition' => 'array',
            'weight' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(NeedsAssessmentAnswer::class, 'question_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeInSection($query, string $section)
    {
        return $query->where('section', $section);
    }
}
