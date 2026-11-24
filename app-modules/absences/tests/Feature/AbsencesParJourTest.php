<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Absences\Application\Services\AbsenceService;
use Modules\Eleves\Application\DTOs\CreateEleveDto;
use Modules\Eleves\Application\Services\EleveService;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;
use Modules\Tenancy\Domain\TenantContext;

uses(RefreshDatabase::class);

it('counts absences per day over a window, empty days included, other schools excluded', function () {
    $this->travelTo('2026-10-12');
    $ecole = Ecole::factory()->create();
    $dir = utilisateurAvecRole(Role::Directeur, $ecole);
    [$classe] = ecoleAvecClasses($ecole, $dir);
    app(TenantContext::class)->forEcole($ecole->id);
    $eleve = app(EleveService::class)->creer($dir, new CreateEleveDto('Rakoto', null, null, null, null, null, $classe->uuid, 'nouvelle', false, false))['eleve'];
    app(AbsenceService::class)->saisir($dir, $classe->uuid, '2026-10-12', 'journee', [['eleve_uuid' => $eleve->uuid, 'justifiee' => false]]);
    $autre = Ecole::factory()->create();

    expect(app(AbsenceService::class)->parJour($dir, 3))->toBe(['2026-10-10' => 0, '2026-10-11' => 0, '2026-10-12' => 1])
        ->and(array_sum(app(AbsenceService::class)->parJour(utilisateurAvecRole(Role::Directeur, $autre), 3)))->toBe(0);
});
