<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'code',
        'name',
        'source',
        'rows',
        'status',
        'size',
        'author',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'rows' => 'integer',
        ];
    }
}
