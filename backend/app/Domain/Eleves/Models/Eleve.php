<?php

declare(strict_types=1);

namespace App\Domain\Eleves\Models;

use App\Domain\Eleves\Enums\StatutEleve;
use App\Domain\Tenancy\BelongsToEcole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

final class Eleve extends Model
{
    use BelongsToEcole, HasUuids, LogsActivity, SoftDeletes;

    protected $table = 'eleves';

    protected $fillable = [
        'matricule', 'nom', 'prenoms', 'sexe', 'date_naissance', 'lieu_naissance',
        'date_entree', 'champs_perso',
    ];

    protected $attributes = ['statut' => 'actif'];

    protected function casts(): array
    {
        return [
            'date_naissance' => 'date',
            'date_entree' => 'date',
            'date_sortie' => 'date',
            'statut' => StatutEleve::class,
            'champs_perso' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        // RG-E1: a matricule correction must leave a trace, so it is part of the whitelist.
        return LogOptions::defaults()
            ->logOnly(['matricule', 'nom', 'prenoms', 'statut', 'date_sortie', 'motif_sortie'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('eleves');
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    public function inscriptionActive(): HasOne
    {
        return $this->hasOne(Inscription::class)->where('statut', 'active')->latestOfMany();
    }
}
