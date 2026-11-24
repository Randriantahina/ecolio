<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Absences\Application\Services\AbsenceService;
use Modules\Eleves\Application\DTOs\CreateEleveDto;
use Modules\Eleves\Application\Services\EleveService;
use Modules\Finance\Application\DTOs\EncaisserDto;
use Modules\Finance\Application\Services\FraisService;
use Modules\Finance\Application\Services\PaiementService;
use Modules\Finance\Domain\Contracts\EcheanceRepository;
use Modules\Identity\Database\Seeders\RolesSeeder;
use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Platform\Domain\Models\Ecole;
use Modules\Scolarite\Application\DTOs\CreateAnneeScolaireDto;
use Modules\Scolarite\Application\Services\AnneeScolaireService;
use Modules\Scolarite\Application\Services\StructureService;
use Modules\Tenancy\Domain\TenantContext;
use RuntimeException;

/** Fictional data only (spec section 10: no real student data outside production). */
final class DemoSeeder extends Seeder
{
    private const ELEVES = [
        ['Rakotoarisoa', 'Hery', 'M'], ['Randrianasolo', 'Miora', 'F'], ['Rasoanaivo', 'Tiana', 'F'],
        ['Andrianarivo', 'Fitiavana', 'M'], ['Razafindrakoto', 'Lalaina', 'F'], ['Ravelojaona', 'Mamy', 'M'],
        ['Rabemananjara', 'Noro', 'F'], ['Ramanantsoa', 'Faly', 'M'], ['Randriamampionona', 'Soa', 'F'],
        ['Rakotomalala', 'Toky', 'M'], ['Raharimanana', 'Volana', 'F'], ['Andriamasy', 'Aina', 'M'],
        ['Razanakolona', 'Hanta', 'F'], ['Rasolofoniaina', 'Njaka', 'M'], ['Ravaoarisoa', 'Mirana', 'F'],
        ['Rajaonarison', 'Kanto', 'F'], ['Ramaroson', 'Tahiry', 'M'], ['Andrianjafy', 'Lova', 'F'],
        ['Rakotonirina', 'Fenitra', 'M'], ['Razafimahefa', 'Santatra', 'F'], ['Randriambololona', 'Mahery', 'M'],
        ['Ratsimbazafy', 'Ony', 'F'], ['Rabenandrasana', 'Bako', 'F'], ['Rakotovao', 'Dina', 'M'],
    ];

    public function run(): void
    {
        // The password is public knowledge, so this must be impossible to run against a real database.
        if (! app()->environment('local')) {
            throw new RuntimeException('DemoSeeder only runs in the local environment.');
        }

        $this->call(RolesSeeder::class);

        $ecole = Ecole::firstOrCreate(
            ['slug' => 'ecole-demo'],
            ['nom' => 'École Démo (données fictives)', 'telephone' => '034 00 000 00', 'adresse' => 'Antananarivo'],
        );

        $directeur = $this->utilisateur($ecole, 'directeur@demo.ecolio.test', 'Directrice Démo', Role::Directeur);
        $this->utilisateur($ecole, 'secretaire@demo.ecolio.test', 'Secrétaire Démo', Role::Secretaire);
        $this->utilisateur($ecole, 'enseignant@demo.ecolio.test', 'Enseignant Démo', Role::Enseignant);

        app(TenantContext::class)->runAs($ecole->id, function () use ($directeur): void {
            // Already populated: running twice must not duplicate pupils or payments.
            if (app(AnneeScolaireService::class)->lister($directeur)->isNotEmpty()) {
                return;
            }
            $this->peupler($directeur);
        });
    }

    private function utilisateur(Ecole $ecole, string $email, string $nom, Role $role): Utilisateur
    {
        $u = Utilisateur::firstOrCreate(
            ['email' => $email],
            ['nom' => $nom, 'mot_de_passe' => 'password', 'ecole_id' => $ecole->id],
        );
        if (! $u->aRole($role)) {
            $u->assignRole($role->value);
        }

        return $u;
    }

    private function peupler(Utilisateur $directeur): void
    {
        app(AnneeScolaireService::class)->creer($directeur, new CreateAnneeScolaireDto('2026-2027', '2026-09-01', '2027-06-30', true));

        $structure = app(StructureService::class);
        $sixieme = $structure->creerNiveau($directeur, ['nom' => '6e', 'cycle' => 'college', 'ordre' => 6]);
        $cinquieme = $structure->creerNiveau($directeur, ['nom' => '5e', 'cycle' => 'college', 'ordre' => 5]);
        $classes = [
            $structure->creerClasse($directeur, ['nom' => '6e A', 'niveau_uuid' => $sixieme->uuid, 'effectif_max' => 30]),
            $structure->creerClasse($directeur, ['nom' => '6e B', 'niveau_uuid' => $sixieme->uuid, 'effectif_max' => 30]),
            $structure->creerClasse($directeur, ['nom' => '5e A', 'niveau_uuid' => $cinquieme->uuid, 'effectif_max' => 30]),
        ];

        $frais = app(FraisService::class);
        $ecolage = $frais->creerType($directeur, ['nom' => 'Écolage', 'periodicite' => 'mensuel']);
        $inscription = $frais->creerType($directeur, ['nom' => 'Frais d\'inscription', 'periodicite' => 'unique']);
        $frais->creerTarif($directeur, ['type_frais_uuid' => $inscription->uuid, 'montant_ar' => 60_000]);
        $frais->creerTarif($directeur, ['type_frais_uuid' => $ecolage->uuid, 'niveau_uuid' => $sixieme->uuid, 'montant_ar' => 45_000]);
        $frais->creerTarif($directeur, ['type_frais_uuid' => $ecolage->uuid, 'niveau_uuid' => $cinquieme->uuid, 'montant_ar' => 50_000]);

        $eleves = app(EleveService::class);
        $creees = [];
        foreach (self::ELEVES as $i => [$nom, $prenoms, $sexe]) {
            $classe = $classes[$i % 3];
            $eleve = $eleves->creer($directeur, new CreateEleveDto(
                nom: $nom, prenoms: $prenoms, sexe: $sexe,
                dateNaissance: (2012 + ($i % 3)).'-'.str_pad((string) (($i % 12) + 1), 2, '0', STR_PAD_LEFT).'-1'.($i % 9),
                lieuNaissance: 'Antananarivo', matricule: null, classeUuid: $classe->uuid,
                typeInscription: 'nouvelle', estRedoublant: false, confirmerDoublon: false,
            ))['eleve'];
            $creees[] = [$eleve, $classe];
        }

        $this->encaisserQuelques($directeur, $creees);
        $this->absences($directeur, $creees);
    }

    /** Mix of full, partial, unpaid and one voided payment so every box state shows up in the demo. */
    private function encaisserQuelques(Utilisateur $directeur, array $creees): void
    {
        $paiements = app(PaiementService::class);
        $echeances = app(EcheanceRepository::class);
        $modes = ['especes', 'mvola', 'orange_money', 'especes'];

        foreach ($creees as $i => [$eleve]) {
            if ($i % 6 === 5) {
                continue; // never paid anything: shows the "dû" state
            }
            $liste = $echeances->forEleve($eleve->id)->filter(fn ($e) => in_array($e->periode, [null, '2026-09', '2026-10'], true));
            $lignes = [];
            foreach ($liste as $e) {
                // Every fourth pupil pays October only in part.
                $montant = ($i % 4 === 3 && $e->periode === '2026-10') ? intdiv($e->montant_ar, 2) : $e->montant_ar;
                $lignes[] = ['echeance_uuid' => $e->uuid, 'montant_ar' => $montant];
            }
            if ($lignes === []) {
                continue;
            }
            $mode = $modes[$i % 4];
            $resultat = $paiements->encaisser($directeur, new EncaisserDto(
                $eleve->uuid, $lignes, $mode, now()->toDateString(),
                $mode === 'especes' ? null : 'REF'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT), null,
            ));

            if ($i === 2) {
                $paiements->annuler($directeur, $resultat['paiement']->uuid, 'Saisi pour le mauvais élève (démo)');
            }
        }
    }

    private function absences(Utilisateur $directeur, array $creees): void
    {
        $service = app(AbsenceService::class);
        foreach ($creees as $i => [$eleve, $classe]) {
            if ($i % 5 !== 1) {
                continue;
            }
            $service->saisir($directeur, $classe->uuid, now()->toDateString(), 'journee', [
                ['eleve_uuid' => $eleve->uuid, 'justifiee' => $i % 2 === 0, 'motif' => $i % 2 === 0 ? 'Maladie' : null],
            ]);
        }
    }
}
