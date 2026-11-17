<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipe extends Model
{
    protected $fillable = [
        'user_id',
        'nom',
        'code',
        'description',
        'logo',
        'statut',
        'active',
        'deleted',
        'inserted_by',
        'updated_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function joueurs(): HasMany
    {
        return $this->hasMany(Joueur::class);
    }
}
