<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Scolarite\Domain\Models\AnneeScolaire;
use Modules\Scolarite\Domain\Models\Classe;
use Modules\Scolarite\Domain\Models\Niveau;
use Modules\Tenancy\Domain\BelongsToEcole;

final class Tarif extends Model
{
    use BelongsToEcole, HasUuids;

    protected $table = 'tarifs';

    protected $fillable = ['montant_ar'];

    protected function casts(): array
    {
        return ['montant_ar' => 'integer'];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function typeFrais(): BelongsTo
    {
        return $this->belongsTo(TypeFrais::class);
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }
}
