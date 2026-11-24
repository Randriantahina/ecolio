<?php

declare(strict_types=1);

namespace Modules\Scolarite\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Scolarite\Domain\Enums\Cycle;
use Modules\Tenancy\Domain\BelongsToEcole;

final class Niveau extends Model
{
    use BelongsToEcole, HasUuids;

    protected $table = 'niveaux';

    protected $fillable = ['nom', 'cycle', 'ordre'];

    // Mirrors the column defaults: a fresh model must expose them without a reload.
    protected $attributes = ['cycle' => 'autre', 'ordre' => 0];

    protected function casts(): array
    {
        return ['cycle' => Cycle::class, 'ordre' => 'integer'];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
