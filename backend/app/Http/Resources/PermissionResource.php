<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
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
            'display_name' => $this->display_name,
            'module' => $this->module,
            'description' => $this->description,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Parsed permission parts for UI convenience
            'scope' => $this->getScope(),
            'action' => $this->getAction(),
        ];
    }

    /**
     * Get the scope from permission name (.own, .team, or null).
     */
    protected function getScope(): ?string
    {
        if (str_ends_with($this->name, '.own')) {
            return 'own';
        }

        if (str_ends_with($this->name, '.team')) {
            return 'team';
        }

        return null;
    }

    /**
     * Get the action from permission name (e.g., 'view', 'create', 'list').
     */
    protected function getAction(): string
    {
        $parts = explode('.', $this->name);

        // Remove module (first part)
        array_shift($parts);

        // Remove scope if present (last part if it's 'own' or 'team')
        if (!empty($parts) && in_array(end($parts), ['own', 'team'])) {
            array_pop($parts);
        }

        return implode('.', $parts) ?: '';
    }
}
