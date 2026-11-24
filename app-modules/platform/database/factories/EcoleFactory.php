<?php

declare(strict_types=1);

namespace Modules\Platform\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Platform\Domain\Models\Ecole;

/** @extends Factory<Ecole> */
final class EcoleFactory extends Factory
{
    protected $model = Ecole::class;

    public function definition(): array
    {
        $nom = 'Ecole '.fake()->unique()->lastName();

        return [
            'nom' => $nom,
            'slug' => Str::slug($nom).'-'.fake()->unique()->numberBetween(1, 99999),
            'telephone' => '034 00 000 00',
            'actif' => true,
        ];
    }
}
