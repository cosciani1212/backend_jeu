<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jeu extends Model
{
    protected $table = 'jeus';

    protected $fillable = [
        'user_id',
        'evenement_id',
        'categorie_id',
        'nom',
        'description',
        'image',
        'nombre_joueurs',
        'nombre_equipes',
        'est_payant',
        'montant_inscription',
        'mode_competition',
        'date_demarrage',
        'date_limite_inscription',
        'statut',
        'active',
        'deleted',
    ];

    protected function casts(): array
    {
        return [
            'nombre_joueurs' => 'integer',
            'nombre_equipes' => 'integer',
            'est_payant' => 'boolean',
            'montant_inscription' => 'decimal:2',
            'date_demarrage' => 'date',
            'date_limite_inscription' => 'date',
            'active' => 'boolean',
            'deleted' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class, 'evenement_id');
    }
}