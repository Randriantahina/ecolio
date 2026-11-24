<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Eleves\Application\DTOs\CreateEleveDto;
use Modules\Eleves\Application\Services\DepartService;
use Modules\Eleves\Application\Services\EleveService;
use Modules\Eleves\Domain\Enums\StatutEleve;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;
use Modules\Tenancy\Domain\TenantContext;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Directeur, $this->ecole);
    [$this->classe] = ecoleAvecClasses($this->ecole, $this->directeur);
    app(TenantContext::class)->forEcole($this->ecole->id);
    $this->creer = fn (string $nom, ?string $classe) => app(EleveService::class)->creer($this->directeur, new CreateEleveDto(
        $nom, null, null, null, null, null, $classe, 'nouvelle', false, false,
    ))['eleve'];
});

it('counts active pupils and those without a class', function () {
    ($this->creer)('Rakoto', $this->classe->uuid);
    ($this->creer)('Rabe', null);

    expect(app(EleveService::class)->effectifs($this->directeur))->toBe(['actifs' => 2, 'sans_classe' => 1]);
});

it('stops counting a pupil who left', function () {
    $eleve = ($this->creer)('Rakoto', $this->classe->uuid);
    app(DepartService::class)->enregistrer($this->directeur, $eleve->uuid, StatutEleve::Transfere, '2026-12-01', null, null);

    expect(app(EleveService::class)->effectifs($this->directeur))->toBe(['actifs' => 0, 'sans_classe' => 0]);
});

it('never counts the pupils of another school (tenant isolation)', function () {
    ($this->creer)('Rakoto', $this->classe->uuid);
    $autre = Ecole::factory()->create();

    expect(app(EleveService::class)->effectifs(utilisateurAvecRole(Role::Directeur, $autre)))->toBe(['actifs' => 0, 'sans_classe' => 0]);
});
