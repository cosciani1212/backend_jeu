<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Evenement extends Model
{
    protected $fillable = [
        'user_id',
        'structure_id',
        'titre',
        'description',
        'lieu',
        'date_debut',
        'date_fin',
        'statut',
        'active',
        'deleted',
        'inserted_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'datetime',
            'date_fin' => 'datetime',
            'active' => 'boolean',
            'deleted' => 'boolean',
        ];
    }
    public function jeus(): HasMany
    {
        return $this->hasMany(Jeu::class, 'evenement_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function jurys(): HasMany
    {
        return $this->hasMany(Jury::class);
    }
}

