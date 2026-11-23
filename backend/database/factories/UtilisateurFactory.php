<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Identity\Models\Utilisateur;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Utilisateur> */
final class UtilisateurFactory extends Factory
{
    protected $model = Utilisateur::class;

    public function definition(): array
    {
        return [
            'nom' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'mot_de_passe' => 'password',
            'actif' => true,
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(['ecole_id' => null]);
    }
}
