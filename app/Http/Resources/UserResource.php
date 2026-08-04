<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'role'              => $this->role ?? ($this->roles->first()?->name ?? 'user'),
            'organization_id'   => $this->organization_id,
            'organization'      => new OrganizationResource($this->whenLoaded('organization')),
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'two_factor_enabled'=> !empty($this->two_factor_secret),
            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
        ];
    }
}
