<?php

declare(strict_types=1);

namespace Modules\Eleves\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Eleves\Database\Factories\EleveFactory;
use Modules\Eleves\Domain\Enums\StatutEleve;
use Modules\Tenancy\Domain\BelongsToEcole;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

final class Eleve extends Model
{
    use BelongsToEcole, HasFactory, HasUuids, LogsActivity, SoftDeletes;

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

    protected static function newFactory(): EleveFactory
    {
        return EleveFactory::new();
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
