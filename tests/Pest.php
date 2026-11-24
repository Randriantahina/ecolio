<?php

use App\Application\Scolarite\DTOs\CreateAnneeScolaireDto;
use App\Application\Scolarite\Services\AnneeScolaireService;
use App\Application\Scolarite\Services\StructureService;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\Utilisateur;
use App\Domain\Platform\Models\Ecole;
use App\Domain\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/** A user holding one role, in the given school (null school = platform super admin). */
function utilisateurAvecRole(Role $role, ?Ecole $ecole = null): Utilisateur
{
    (new RolesSeeder)->run();

    $user = $role === Role::SuperAdmin
        ? Utilisateur::factory()->superAdmin()->create()
        : Utilisateur::factory()->for($ecole ?? Ecole::factory()->create())->create();

    return $user->assignRole($role->value);
}

/**
 * Puts a Livewire/Filament test inside the school panel exactly as the tenant middleware does in a real
 * request: signed in, panel and tenant selected, TenantContext bound to the school.
 */
function dansLePanelEcole(Utilisateur $user, Ecole $ecole): Utilisateur
{
    test()->actingAs($user);
    Filament\Facades\Filament::setCurrentPanel('ecole');
    Filament\Facades\Filament::setTenant($ecole);
    app(TenantContext::class)->forEcole($ecole->id);

    return $user;
}

/** A school with a current year, one level and two classes (the first holds a single pupil at most). */
function ecoleAvecClasses(Ecole $ecole, Utilisateur $directeur): array
{
    return app(TenantContext::class)->runAs($ecole->id, function () use ($directeur) {
        app(AnneeScolaireService::class)
            ->creer($directeur, new CreateAnneeScolaireDto('2026-2027', '2026-09-01', '2027-06-30', true));
        $structure = app(StructureService::class);
        $niveau = $structure->creerNiveau($directeur, ['nom' => '6e']);

        return [
            $structure->creerClasse($directeur, ['nom' => '6e A', 'niveau_uuid' => $niveau->uuid, 'effectif_max' => 1]),
            $structure->creerClasse($directeur, ['nom' => '6e B', 'niveau_uuid' => $niveau->uuid]),
        ];
    });
}
