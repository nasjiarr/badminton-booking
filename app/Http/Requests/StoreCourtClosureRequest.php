<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourtClosureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:tournament,maintenance,holiday,special_event'],
            'court_id' => ['nullable', 'exists:courts,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama kegiatan / turnamen wajib diisi.',
            'type.required' => 'Pilih jenis jadwal penutupan.',
            'type.in' => 'Jenis penutupan tidak valid.',
            'start_date.required' => 'Tanggal mulai wajib ditentukan.',
            'end_date.required' => 'Tanggal selesai wajib ditentukan.',
            'end_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'start_time.required' => 'Jam mulai wajib diisi.',
            'end_time.required' => 'Jam selesai wajib diisi.',
            'end_time.after' => 'Jam selesai harus lebih akhir daripada jam mulai.',
            'court_id.exists' => 'Lapangan yang dipilih tidak ditemukan.',
        ];
    }
}
