<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->name,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'telephone' => $this->telephone,

            'role' => $this->whenLoaded('role', function () {
                return [
                    'id' => $this->role?->id,
                    'nom' => $this->role?->nom,
                ];
            }),

            'structure' => $this->whenLoaded('structure', function () {
                return [
                    'id' => $this->structure?->id,
                    'nom' => $this->structure?->nom,
                ];
            }),

            'email_verified_at' => $this->email_verified_at,
            'active' => $this->active,
            'created_at' => $this->created_at,
        ];
    }
}


