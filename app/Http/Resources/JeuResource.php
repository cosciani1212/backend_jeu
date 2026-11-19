<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JeuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'categorie_id' => $this->categorie_id,
            'categorie' => new CategorieResource($this->whenLoaded('categorie')),
            'evenement_id' => $this->evenement_id,
            'evenement' => new EvenementResource($this->whenLoaded('evenement')),
            'nom' => $this->nom,
            'description' => $this->description,
            'image' => $this->image,
            'nombre_joueurs' => $this->nombre_joueurs,
            'nombre_equipes' => $this->nombre_equipes,
            'est_payant' => $this->est_payant,
            'montant_inscription' => $this->montant_inscription,
            'mode_competition' => $this->mode_competition,
            'date_demarrage' => $this->date_demarrage?->format('Y-m-d'),
            'date_limite_inscription' => $this->date_limite_inscription?->format('Y-m-d'),
            'statut' => $this->statut,
            'active' => $this->active,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}       