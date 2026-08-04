<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $orgId = $this->route('organization') ? $this->route('organization')->id : $this->id;

        return [
            'name'      => 'required|string|max:255|unique:organizations,name,' . $orgId,
            'shortname' => 'required|string|max:50|unique:organizations,shortname,' . $orgId,
        ];
    }
}
