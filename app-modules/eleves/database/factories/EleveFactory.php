<?php

declare(strict_types=1);

namespace Modules\Eleves\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Eleves\Domain\Models\Eleve;

/** @extends Factory<Eleve> ecole_id is filled by BelongsToEcole from the current tenant. */
final class EleveFactory extends Factory
{
    protected $model = Eleve::class;

    public function definition(): array
    {
        return [
            'matricule' => 'T-'.fake()->unique()->numerify('######'),
            'nom' => fake()->lastName(),
            'prenoms' => fake()->firstName(),
            'champs_perso' => [],
        ];
    }
}
