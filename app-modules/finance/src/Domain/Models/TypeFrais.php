<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Domain\Enums\Periodicite;
use Modules\Tenancy\Domain\BelongsToEcole;

final class TypeFrais extends Model
{
    use BelongsToEcole, HasUuids;

    protected $table = 'types_frais';

    protected $fillable = ['nom', 'periodicite', 'est_obligatoire', 'actif'];

    protected $attributes = ['est_obligatoire' => true, 'actif' => true];

    protected function casts(): array
    {
        return ['periodicite' => Periodicite::class, 'est_obligatoire' => 'boolean', 'actif' => 'boolean'];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
