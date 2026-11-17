<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categorie extends Model
{
     protected $fillable = [
        'user_id',
        'nom',
        'description',
        'image',
        'active',
        'deleted',
        'inserted_by',
        'updated_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jeux(): HasMany
    {
        return $this->hasMany(Jeu::class);
    }
}

