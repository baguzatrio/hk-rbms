<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roomId = $this->route('room') ? $this->route('room')->id : $this->id;

        return [
            'name'        => 'required|string|max:255|unique:rooms,name,' . $roomId,
            'floor'       => 'required|string|max:50',
            'capacity'    => 'required|integer|min:1',
            'status'      => 'required|in:active,maintenance',
            'description' => 'nullable|string|max:1000',
        ];
    }
}
