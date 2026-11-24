<?php

declare(strict_types=1);

namespace App\Domain\Platform\Contracts;

use App\Domain\Platform\Models\Ecole;

interface EcoleRepository
{
    public function findOrFail(int $id): Ecole;

    public function save(Ecole $ecole): void;
}
