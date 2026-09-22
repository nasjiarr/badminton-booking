<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'court_id' => 'required|integer|exists:courts,id',
            'booking_date' => 'required|date|date_format:Y-m-d|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'court_id.required' => 'Silakan pilih lapangan terlebih dahulu.',
            'court_id.exists' => 'Lapangan yang dipilih tidak valid.',
            'booking_date.required' => 'Tanggal booking wajib dipilih.',
            'booking_date.date_format' => 'Format tanggal booking tidak valid (YYYY-MM-DD).',
            'booking_date.after_or_equal' => 'Tanggal booking tidak boleh tanggal yang sudah lewat.',
            'start_time.required' => 'Jam mulai booking wajib dipilih.',
            'start_time.date_format' => 'Format jam mulai tidak valid (JJ:MM).',
            'end_time.required' => 'Jam selesai booking wajib dipilih.',
            'end_time.date_format' => 'Format jam selesai tidak valid (JJ:MM).',
            'end_time.after' => 'Jam selesai harus lebih besar dari jam mulai.',
            'notes.max' => 'Catatan maksimal 500 karakter.',
        ];
    }
}

