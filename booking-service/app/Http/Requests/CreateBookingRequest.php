<?php
namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // provider_id et service_id sont validés en temps réel
            // contre le Provider Service dans BookingService::createBooking()
            'provider_id'    => ['required', 'integer'],
            'service_id'     => ['required', 'integer'],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'provider_id.required'          => 'L\'identifiant du prestataire est obligatoire.',
            'service_id.required'           => 'L\'identifiant du service est obligatoire.',
            'scheduled_date.after_or_equal' => 'La date doit être aujourd\'hui ou dans le futur.',
            'scheduled_time.date_format'    => 'Format d\'heure invalide (HH:MM).',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Erreurs de validation.',
            'data'    => $validator->errors(),
        ], 422));
    }
}
