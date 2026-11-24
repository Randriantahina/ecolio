<?php

declare(strict_types=1);

namespace Modules\Scolarite\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Scolarite\Domain\Models\Classe;
use Modules\Scolarite\Domain\Models\Niveau;

/** Levels and classes: the school structure, always read through a school id. */
interface StructureRepository
{
    /** @return Collection<int, Niveau> */
    public function niveaux(int $ecoleId): Collection;

    public function saveNiveau(Niveau $niveau): void;

    public function niveauOrFail(string $uuid, int $ecoleId): Niveau;

    /** @return Collection<int, Classe> */
    public function classesOfYear(int $ecoleId, int $anneeId): Collection;

    public function saveClasse(Classe $classe): void;

    public function classeOrFail(string $uuid, int $ecoleId): Classe;

    public function deleteClasse(Classe $classe): void;
}
