<?php

declare(strict_types=1);

namespace Modules\Platform\Infrastructure\Persistence\Eloquent;

use Modules\Platform\Domain\Contracts\EcoleRepository;
use Modules\Platform\Domain\Models\Ecole;

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
