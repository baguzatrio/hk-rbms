<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'room_id'        => $this->room_id,
            'requester_id'   => $this->requester_id,
            'title'          => $this->title,
            'start_date'     => $this->start_date?->format('Y-m-d'),
            'end_date'       => $this->end_date?->format('Y-m-d'),
            'start_hour'     => $this->start_hour ? substr($this->start_hour, 0, 5) : null,
            'finish_hour'    => $this->finish_hour ? substr($this->finish_hour, 0, 5) : null,
            'status'         => $this->status,
            'pic_name'       => $this->pic_name,
            'participants'   => $this->participants,
            'notes'          => $this->notes,
            'remarks'        => $this->remarks,
            'rejection_note' => $this->rejection_note ?? $this->remarks,
            'approved_by'    => $this->approved_by,
            'approved_at'    => $this->approved_at?->toIso8601String(),
            'cancelled_at'   => $this->cancelled_at?->toIso8601String(),
            'room'           => new RoomResource($this->whenLoaded('room')),
            'requester'      => new UserResource($this->whenLoaded('requester')),
            'approver'       => new UserResource($this->whenLoaded('approver')),
            'can_cancel'     => $this->isPending(),
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
