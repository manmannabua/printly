<?php

namespace App\Http\Resources;

use App\Helpers\AuditSanitizer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user' => $this->when(
                $this->relationLoaded('user'),
                fn () => $this->user ? [
                    'id' => $this->user->id,
                    'email' => $this->user->email,
                ] : null
            ),
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'action' => $this->action,
            'old_values' => AuditSanitizer::sanitize($this->old_values),
            'new_values' => AuditSanitizer::sanitize($this->new_values),
            'changed_fields' => $this->changed_fields,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
