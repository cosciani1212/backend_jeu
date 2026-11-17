<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'code' => $this->code,
            'description' => $this->description,
            'logo' => $this->logo,
            'statut' => $this->statut,

            'user' => [
                'id' => $this->user?->id,
                'nom' => $this->user?->name,
                'prenom' => $this->user?->prenom,
            ],

            'created_at' => $this->created_at,
        ];
    }
}