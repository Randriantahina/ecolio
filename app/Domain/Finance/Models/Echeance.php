<?php

declare(strict_types=1);

namespace App\Domain\Finance\Models;

use App\Domain\Eleves\Models\Eleve;
use App\Domain\Finance\Enums\StatutEcheance;
use App\Domain\Finance\ValueObjects\Money;
use App\Domain\Tenancy\BelongsToEcole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Echeance extends Model
{
    use BelongsToEcole, HasUuids;

    protected $table = 'echeances';

    protected $fillable = ['periode', 'libelle', 'montant_ar', 'remise_ar', 'remise_motif', 'date_limite'];

    protected $attributes = ['statut' => 'du', 'remise_ar' => 0];

    protected function casts(): array
    {
        return ['montant_ar' => 'integer', 'remise_ar' => 'integer', 'date_limite' => 'date', 'statut' => StatutEcheance::class];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /** What the family actually owes once any discount is applied. */
    public function net(): Money
    {
        return Money::ariary(max(0, $this->montant_ar - $this->remise_ar));
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function typeFrais(): BelongsTo
    {
        return $this->belongsTo(TypeFrais::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(PaiementLigne::class);
    }
}
