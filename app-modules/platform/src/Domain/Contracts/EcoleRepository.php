<?php

declare(strict_types=1);

namespace Modules\Platform\Domain\Contracts;

use Modules\Platform\Domain\Models\Ecole;

interface EcoleRepository
{
    public function findOrFail(int $id): Ecole;

    public function save(Ecole $ecole): void;
}
