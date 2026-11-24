<?php

declare(strict_types=1);

namespace App\Domain\Finance\Models;

use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Niveau;
use App\Domain\Tenancy\BelongsToEcole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
