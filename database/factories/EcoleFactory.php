<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Platform\Models\Ecole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
