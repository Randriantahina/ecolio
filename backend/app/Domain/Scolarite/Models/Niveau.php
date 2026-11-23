<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Models;

use App\Domain\Scolarite\Enums\Cycle;
use App\Domain\Tenancy\BelongsToEcole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

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
