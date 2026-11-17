<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StructureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'sigle' => $this->sigle,
            'description' => $this->description,
            'adresse' => $this->adresse,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'logo' => $this->logo,

            'user' => [
                'id' => $this->user?->id,
                'nom' => $this->user?->name,
                'prenom' => $this->user?->prenom,
            ],

            'created_at' => $this->created_at,
        ];
    }
}
