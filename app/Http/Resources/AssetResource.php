<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            'id' => $this->id,
            'host_name' => $this->host_name,
            'ip_address_v4' => $this->ip_address_v4,
            'os' => str_contains(strtolower($this->os), strtolower('Windows Server')) ? 'Windows' : $this->os,
            'rti_score' => $this->rti_score,
            'severity' => $this->severity,
            'comment' => $this->when($this->comment, $this->comment),
            'type' => $this->type,
            'last_scanned' => $this->last_scanned,
            'agent_status' => $this->agent_status,
            'vulnerabilities_count' => $this->vulnerabilities_count,
            'tag_value' => $this->tag_value,
        ];
    }
}
