<?php

declare(strict_types=1);

namespace App\Domain\Finance\Models;

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
}
