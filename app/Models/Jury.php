<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Jury extends Model
{
    protected $fillable = [
        'evenement_id',
        'nom',
        'contact',
        'active',
        'deleted',
        'inserted_by',
        'updated_by',
        'statut',
    ];

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }
    public function createur()
    {
        return $this->belongsTo(User::class, 'inserted_by');
    }
}


