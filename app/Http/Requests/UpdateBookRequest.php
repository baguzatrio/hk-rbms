<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id'      => 'required|exists:rooms,id',
            'title'        => 'required|string|max:255',
            'start_date'   => 'required|date|after_or_equal:today',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'start_hour'   => 'required|date_format:H:i',
            'finish_hour'  => 'required|date_format:H:i|after:start_hour',
            'notes'        => 'nullable|string|max:1000',
            'requester_id' => 'required|exists:users,id',
            'participants' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required'          => 'Ruangan rapat wajib dipilih.',
            'room_id.exists'            => 'Ruangan rapat tidak valid.',
            'title.required'            => 'Agenda rapat wajib diisi.',
            'start_date.required'       => 'Tanggal rapat wajib diisi.',
            'start_date.after_or_equal' => 'Tanggal rapat tidak boleh sebelum hari ini.',
            'end_date.after_or_equal'   => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'start_hour.required'       => 'Jam mulai wajib diisi.',
            'finish_hour.required'      => 'Jam selesai wajib diisi.',
            'finish_hour.after'         => 'Jam selesai harus setelah jam mulai.',
            'requester_id.required'     => 'Pemohon rapat wajib dipilih.',
        ];
    }
}
