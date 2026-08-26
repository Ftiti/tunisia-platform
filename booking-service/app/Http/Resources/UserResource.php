<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    // Désactiver le wrapper "data" du niveau supérieur (déjà géré dans nos réponses)
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'phone'             => $this->phone,
            'role'              => $this->role,
            'permissions'       => $this->permissions ?? [],
            'avatar'            => $this->avatar
                                    ? asset('storage/' . $this->avatar)
                                    : null,
            'is_active'         => $this->is_active,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at'        => $this->created_at->toISOString(),
            'updated_at'        => $this->updated_at->toISOString(),
        ];
    }
}
