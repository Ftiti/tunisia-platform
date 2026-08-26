<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        $validPerms = implode(',', [
            'users.view','users.create','users.edit','users.delete',
            'providers.view','providers.create','providers.edit','providers.delete',
            'orders.view','orders.edit',
            'payments.view','payments.refund',
            'settings.view','settings.edit',
            'reports.view','reports.export',
        ]);

        return [
            'name'          => ['sometimes', 'string', 'max:255'],
            'email'         => ['sometimes', 'email:rfc', "unique:users,email,{$userId}"],
            'password'      => ['sometimes', 'string', 'min:8'],
            'role'          => ['sometimes', 'in:super_admin,admin,manager,moderator'],
            'phone'         => ['nullable', 'string', 'regex:/^[+0-9]{8,20}$/'],
            'is_active'     => ['sometimes', 'boolean'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', "in:{$validPerms}"],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'     => 'Cet email est déjà utilisé.',
            'role.in'          => 'Rôle invalide.',
            'phone.regex'      => 'Numéro de téléphone invalide.',
            'permissions.*.in' => 'Permission invalide.',
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
