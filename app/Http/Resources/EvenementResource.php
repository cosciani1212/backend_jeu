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
            'date_debut' => $this->date_debut,
            'date_fin' => $this->date_fin,
            'statut' => $this->statut,

            'user' => [
                'id' => $this->user?->id,
                'nom' => $this->user?->name,
            ],

            'structure' => [
                'id' => $this->structure?->id,
                'nom' => $this->structure?->nom,
            ],

            'jeu' => $this->jeu,

            'created_at' => $this->created_at,
        ];
    }
}

