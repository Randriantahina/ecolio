<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Eleves\Domain\Models\Eleve;
use Modules\Finance\Domain\Enums\ModePaiement;
use Modules\Finance\Domain\Enums\StatutPaiement;
use Modules\Tenancy\Domain\BelongsToEcole;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

final class Paiement extends Model
{
    use BelongsToEcole, HasUuids, LogsActivity;

    protected $table = 'paiements';

    protected $guarded = [];

    protected $attributes = ['statut' => 'valide'];

    protected function casts(): array
    {
        return [
            'date_paiement' => 'date',
            'total_ar' => 'integer',
            'mode' => ModePaiement::class,
            'statut' => StatutPaiement::class,
            'annule_le' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        // Payments and voids are audited (spec 6.5, RG-P4); the amount is part of the trail.
        return LogOptions::defaults()
            ->logOnly(['total_ar', 'mode', 'statut', 'motif_annulation'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('paiements');
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(PaiementLigne::class);
    }

    public function recu(): HasOne
    {
        return $this->hasOne(Recu::class);
    }
}
