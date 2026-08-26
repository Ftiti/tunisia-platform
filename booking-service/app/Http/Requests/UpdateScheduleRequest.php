<?php
namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'schedules'                  => ['required', 'array'],
            'schedules.*.day_of_week'    => ['required', 'integer', 'between:0,6'],
            'schedules.*.is_closed'      => ['required', 'boolean'],
            'schedules.*.open_time'      => ['nullable', 'date_format:H:i', 'required_if:schedules.*.is_closed,false'],
            'schedules.*.close_time'     => ['nullable', 'date_format:H:i', 'required_if:schedules.*.is_closed,false', 'after:schedules.*.open_time'],
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
