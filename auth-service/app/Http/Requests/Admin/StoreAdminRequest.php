<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validPerms = implode(',', [
            'users.view','users.create','users.edit','users.delete',
            'providers.view','providers.create','providers.edit','providers.delete',
            'orders.view','orders.edit',
            'payments.view','payments.refund',
            'settings.view','settings.edit',
            'reports.view','reports.export',
        ]);

        return [
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email:rfc', 'unique:users,email'],
            'password'      => ['required', 'string', 'min:8'],
            'role'          => ['required', 'in:super_admin,admin,manager,moderator'],
            'phone'         => ['nullable', 'string', 'regex:/^[+0-9]{8,20}$/'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', "in:{$validPerms}"],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'Le nom est obligatoire.',
            'email.required'    => 'L\'email est obligatoire.',
            'email.email'       => 'Format d\'email invalide.',
            'email.unique'      => 'Cet email est déjà utilisé.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min'      => 'Minimum 8 caractères.',
            'role.required'     => 'Le rôle est obligatoire.',
            'role.in'           => 'Rôle invalide.',
            'phone.regex'       => 'Numéro de téléphone invalide.',
            'permissions.*.in'  => 'Permission invalide.',
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
