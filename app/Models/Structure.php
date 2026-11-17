<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Structure extends Model
{
    protected $fillable = [
        'user_id',
        'nom',
        'sigle',
        'description',
        'adresse',
        'telephone',
        'email',
        'logo',
        'active',
        'deleted',
        'inserted_by',
        'updated_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function evenements(): HasMany
    {
        return $this->hasMany(Evenement::class);
    }
}

