<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Contracts;

use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Niveau;
use Illuminate\Support\Collection;

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
