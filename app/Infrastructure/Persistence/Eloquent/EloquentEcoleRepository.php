<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Platform\Contracts\EcoleRepository;
use App\Domain\Platform\Models\Ecole;

final class EloquentEcoleRepository implements EcoleRepository
{
    public function findOrFail(int $id): Ecole
    {
        return Ecole::query()->findOrFail($id);
    }

    public function save(Ecole $ecole): void
    {
        $ecole->save();
    }
}
