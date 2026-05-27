<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Report */
class ReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->code,
            'name' => $this->name,
            'source' => $this->source,
            'rows' => $this->rows,
            'status' => $this->status,
            'createdAt' => $this->created_at?->toIso8601String(),
            'size' => $this->size,
            'author' => $this->author,
            'hasPdf' => $this->hasPdf(),
        ];
    }
}
