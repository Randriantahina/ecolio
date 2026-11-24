<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Dynamique\Application\Services\ChampService;
use Modules\Dynamique\Domain\Contracts\ChampRepository;
use Modules\Dynamique\Domain\Contracts\LecteurValeursChamps;
use Modules\Dynamique\Domain\Enums\EntiteChamp;
use Modules\Dynamique\Domain\Enums\TypeChamp;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;
use Modules\Tenancy\Domain\TenantContext;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Administration, $this->ecole);
    app(TenantContext::class)->forEcole($this->ecole->id);
    $this->champs = app(ChampService::class);
});

it('derives an immutable slug key from the label and keeps it when renamed (RG-D2)', function () {
    $champ = $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Quartier d\'origine', TypeChamp::Texte);

    expect($champ->cle)->toBe('quartier_dorigine');

    $renomme = $this->champs->renommer($this->directeur, $champ->uuid, 'Quartier');
    expect($renomme->libelle)->toBe('Quartier')->and($renomme->cle)->toBe('quartier_dorigine');
});

it('suffixes the key when the slug already exists for the entity', function () {
    $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Quartier', TypeChamp::Texte);
    $second = $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Quartier', TypeChamp::Texte);

    expect($second->cle)->toBe('quartier_2');
});

it('allows the same key on another entity', function () {
    $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Quartier', TypeChamp::Texte);

    expect($this->champs->creer($this->directeur, EntiteChamp::Tuteur, 'Quartier', TypeChamp::Texte)->cle)->toBe('quartier');
});

it('refuses a 31st active custom field per entity (RG-D4) but counts only active ones', function () {
    foreach (range(1, 30) as $i) {
        $this->champs->creer($this->directeur, EntiteChamp::Eleve, "Champ $i", TypeChamp::Texte);
    }

    expect(fn () => $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Un de trop', TypeChamp::Texte))
        ->toThrow(ValidationException::class);

    $this->champs->desactiver($this->directeur, $this->champs->lister($this->directeur, EntiteChamp::Eleve)->first()->uuid);
    expect($this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Un de trop', TypeChamp::Texte)->actif)->toBeTrue();
});

it('requires choices for a list field', function () {
    expect(fn () => $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Religion', TypeChamp::Liste))
        ->toThrow(ValidationException::class);

    $champ = $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Religion', TypeChamp::Liste, ['Catholique', 'Protestant']);
    expect($champ->options)->toBe(['Catholique', 'Protestant']);
});

it('deactivates instead of deleting and can reactivate (RG: data kept)', function () {
    $champ = $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Quartier', TypeChamp::Texte);

    $this->champs->desactiver($this->directeur, $champ->uuid);
    expect($champ->fresh()->actif)->toBeFalse()->and($champ->fresh()->desactive_le)->not->toBeNull()
        ->and($this->champs->lister($this->directeur, EntiteChamp::Eleve))->toHaveCount(0)
        ->and($this->champs->lister($this->directeur, EntiteChamp::Eleve, false))->toHaveCount(1);

    $this->champs->reactiver($this->directeur, $champ->uuid);
    expect($champ->fresh()->actif)->toBeTrue()->and($champ->fresh()->desactive_le)->toBeNull();
});

it('validates and casts values from the field definitions (RG-D6)', function () {
    $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Quartier', TypeChamp::Texte, null, true);
    $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Boursier', TypeChamp::Booleen);
    $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Fratrie', TypeChamp::Nombre);
    $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Religion', TypeChamp::Liste, ['Catholique', 'Protestant']);
    $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Telephone pere', TypeChamp::Telephone);
    $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Arrivee', TypeChamp::Date);

    $propre = $this->champs->valider($this->directeur, EntiteChamp::Eleve, [
        'quartier' => 'Ambohitsoa', 'boursier' => '1', 'fratrie' => '3', 'religion' => 'Protestant',
        'telephone_pere' => '034 00 000 00', 'arrivee' => '2026-09-01', 'inconnu' => 'ignore',
    ]);

    expect($propre)->toBe([
        'quartier' => 'Ambohitsoa', 'boursier' => true, 'fratrie' => 3, 'religion' => 'Protestant',
        'telephone_pere' => '034 00 000 00', 'arrivee' => '2026-09-01',
    ]);
});

it('rejects values that do not fit the field type, keyed by field', function (string $cle, mixed $valeur) {
    $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Quartier', TypeChamp::Texte, null, true);
    $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Fratrie', TypeChamp::Nombre);
    $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Religion', TypeChamp::Liste, ['Catholique']);
    $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Telephone', TypeChamp::Telephone);
    $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Arrivee', TypeChamp::Date);

    try {
        $this->champs->valider($this->directeur, EntiteChamp::Eleve, ['quartier' => 'X', $cle => $valeur]);
        $this->fail('Expected a validation error');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey("champs_perso.$cle");
    }
})->with([
    'number' => ['fratrie', 'abc'], 'list' => ['religion', 'Bouddhiste'],
    'phone' => ['telephone', 'pas un numero'], 'date' => ['arrivee', '31/31/2026'],
]);

it('requires a mandatory field and ignores deactivated ones', function () {
    $quartier = $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Quartier', TypeChamp::Texte, null, true);

    expect(fn () => $this->champs->valider($this->directeur, EntiteChamp::Eleve, []))->toThrow(ValidationException::class);

    $this->champs->desactiver($this->directeur, $quartier->uuid);
    expect($this->champs->valider($this->directeur, EntiteChamp::Eleve, ['quartier' => 'X']))->toBe([]);
});

it('refuses a type change when existing values do not convert (RG-D3) and lists the rows', function () {
    $champ = $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Fratrie', TypeChamp::Texte);
    $lecteur = new class implements LecteurValeursChamps
    {
        public function supporte(EntiteChamp $entite): bool
        {
            return $entite === EntiteChamp::Eleve;
        }

        public function valeurs(int $ecoleId, EntiteChamp $entite, string $cle): array
        {
            return ['MAT-001 Rakoto' => '3', 'MAT-002 Rabe' => 'beaucoup'];
        }
    };
    $service = new ChampService(app(ChampRepository::class), [$lecteur]);

    try {
        $service->changerType($this->directeur, $champ->uuid, TypeChamp::Nombre, null);
        $this->fail('Expected a refusal');
    } catch (ValidationException $e) {
        expect($e->errors()['type'][0])->toContain('MAT-002 Rabe')->not->toContain('MAT-001');
    }
    expect($champ->fresh()->type)->toBe(TypeChamp::Texte);
});

it('changes the type when every value converts', function () {
    $champ = $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Fratrie', TypeChamp::Texte);

    expect($this->champs->changerType($this->directeur, $champ->uuid, TypeChamp::Nombre, null)->type)->toBe(TypeChamp::Nombre);
});

it('never exposes the fields of another school (tenant isolation)', function () {
    $this->champs->creer($this->directeur, EntiteChamp::Eleve, 'Quartier', TypeChamp::Texte);
    $autre = Ecole::factory()->create();
    $dirAutre = utilisateurAvecRole(Role::Administration, $autre);

    expect($this->champs->lister($dirAutre, EntiteChamp::Eleve))->toHaveCount(0);
});
