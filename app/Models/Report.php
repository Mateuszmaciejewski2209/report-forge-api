<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'code',
        'name',
        'source',
        'rows',
        'status',
        'size',
        'author',
        'csv_path',
        'analytics',
        'pdf_path',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'rows' => 'integer',
            'analytics' => 'array',
        ];
    }

    public function hasPdf(): bool
    {
        return $this->pdf_path !== null && $this->pdf_path !== '';
    }
}
