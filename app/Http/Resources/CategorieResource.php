<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategorieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'image' => $this->image,

            'user' => [
                'id' => $this->user?->id,
                'nom' => $this->user?->name,
                'prenom' => $this->user?->prenom,
            ],

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
