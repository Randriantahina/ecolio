<?php

declare(strict_types=1);

namespace Modules\Scolarite\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenancy\Domain\BelongsToEcole;

final class Classe extends Model
{
    use BelongsToEcole, HasUuids, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = ['nom', 'effectif_max'];

    protected function casts(): array
    {
        return ['effectif_max' => 'integer'];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }
}
