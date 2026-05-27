<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'company' => $this->company,
            'job_title' => $this->job_title,
            'company_logo_url' => $this->company_logo
                ? '/storage/'.ltrim($this->company_logo, '/')
                : null,
            'brand_color' => $this->brand_color,
            'avatar' => $this->avatar,
        ];
    }
}
