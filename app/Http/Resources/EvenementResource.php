<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvenementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'description' => $this->description,
            'lieu' => $this->lieu,
            'date_debut' => $this->date_debut?->format('Y-m-d H:i:s'),
            'date_fin' => $this->date_fin?->format('Y-m-d H:i:s'),
            'statut' => $this->statut,
            'active' => $this->active,

            'user' => [
                'id' => $this->user?->id,
                'nom' => $this->user?->name,
            ],

            'structure' => [
                'id' => $this->structure?->id,
                'nom' => $this->structure?->nom,
            ],

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'jeus' => JeuResource::collection($this->whenLoaded('jeus')),
        ];
    }
}