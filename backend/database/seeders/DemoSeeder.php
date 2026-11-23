<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\Utilisateur;
use App\Domain\Platform\Models\Ecole;
use Illuminate\Database\Seeder;
use RuntimeException;

/** Fictional data only (spec section 10: no real student data outside production). */
final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // The password is public knowledge, so this must be impossible to run against a real database.
        if (! app()->environment('local')) {
            throw new RuntimeException('DemoSeeder only runs in the local environment.');
        }

        $this->call(RolesSeeder::class);

        $ecole = Ecole::firstOrCreate(
            ['slug' => 'ecole-demo'],
            ['nom' => 'École Démo (données fictives)', 'telephone' => '034 00 000 00'],
        );

        $directeur = Utilisateur::firstOrCreate(
            ['email' => 'directeur@demo.ecolio.test'],
            ['nom' => 'Directrice Démo', 'mot_de_passe' => 'password', 'ecole_id' => $ecole->id],
        );

        if (! $directeur->aRole(Role::Directeur)) {
            $directeur->assignRole(Role::Directeur->value);
        }
    }
}
