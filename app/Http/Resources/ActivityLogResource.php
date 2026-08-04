<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'action'      => $this->log_name,
            'model_type'  => $this->subject_type ? class_basename($this->subject_type) : null,
            'model_id'    => $this->subject_id,
            'description' => $this->description,
            'event'       => $this->event,
            'ip_address'  => $this->getProperty('ip_address'),
            'user_agent'  => $this->getProperty('user_agent'),
            'properties'  => $this->properties,
            'user'        => $this->causer ? [
                'id'    => $this->causer->id,
                'name'  => $this->causer->name,
                'email' => $this->causer->email,
            ] : null,
            'created_at'  => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
