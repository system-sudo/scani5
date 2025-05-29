<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role_name' => $this->role_name,
            'organizations_count' => $this->when($this->organizations_count, $this->organizations_count),
            'status' => $this->status,
            'is_locked' => $this->is_locked ? true : false,
            'regenerate_reason' => $this->reason
        ];
    }
}
