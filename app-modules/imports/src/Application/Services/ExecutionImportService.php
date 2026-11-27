<?php

declare(strict_types=1);

namespace Modules\Imports\Application\Services;

use Illuminate\Support\Facades\Gate;
use Modules\Eleves\Application\DTOs\CreateEleveDto;
use Modules\Eleves\Application\DTOs\UpdateEleveDto;
use Modules\Eleves\Application\Services\EleveService;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Imports\Application\DTOs\ResultatLigne;
use Modules\Imports\Domain\Contracts\ImportRepository;
use Modules\Imports\Domain\Enums\StatutImport;
use Modules\Imports\Domain\Enums\StatutLigneImport;
use Modules\Imports\Domain\Models\Import;
use Modules\Imports\Domain\Models\ImportLigne;
use Throwable;

/** Step 5, run by the queue worker: the only place where staged rows become pupils. */
final class ExecutionImportService
{
    public function __construct(
        private readonly ImportRepository $imports,
        private readonly ValidationLigneEleve $validation,
        private readonly ImportService $service,
        private readonly EleveService $eleves,
    ) {}

    public function executer(Utilisateur $acteur, string $uuid): Import
    {
        Gate::forUser($acteur)->authorize('create', Import::class);
        $import = $this->imports->findOrFail($uuid, (int) $acteur->ecole_id);

        $lignes = $this->imports->lignes($import);
        $contexte = $this->service->contexte($acteur, $import, $lignes->pluck('donnees')->all());
        $crees = $misAJour = $enErreur = 0;
        $rapport = [];

        foreach ($lignes as $ligne) {
            if ($ligne->statut === StatutLigneImport::Exclue) {
                continue;
            }
            if ($ligne->statut === StatutLigneImport::Erreur) {
                $enErreur++;
                $rapport[] = ['numero' => $ligne->numero, 'messages' => collect($ligne->erreurs)->pluck('message')->all()];

                continue;
            }

            try {
                // Judged again now: the school may have changed since staging (a matricule taken meanwhile).
                $resultat = $this->validation->valider($acteur, $ligne->donnees, $contexte);
                if ($resultat->estBloquant($ligne->confirmee)) {
                    throw new \DomainException(collect($resultat->erreurs)->pluck('message')->implode(' '));
                }
                $resultat->action === 'creer' ? $crees++ : $misAJour++;
                $this->ecrire($acteur, $ligne, $resultat);
            } catch (Throwable $e) {
                $enErreur++;
                $rapport[] = ['numero' => $ligne->numero, 'messages' => [$e->getMessage()]];
                $ligne->forceFill(['statut' => StatutLigneImport::Erreur->value])->save();
                // The counter was bumped before the write: take it back.
                if (isset($resultat)) {
                    $resultat->action === 'creer' ? $crees-- : $misAJour--;
                }
            }
        }

        $import->forceFill([
            'statut' => StatutImport::Termine, 'crees' => $crees, 'mis_a_jour' => $misAJour, 'en_erreur' => $enErreur,
            'ignores' => $lignes->filter(fn (ImportLigne $l) => $l->statut === StatutLigneImport::Exclue)->count(),
            'rapport' => $rapport, 'termine_le' => now(),
        ]);
        $this->imports->save($import);

        return $import;
    }

    private function ecrire(Utilisateur $acteur, ImportLigne $ligne, ResultatLigne $r): void
    {
        $v = $r->valeurs;

        if ($r->action === 'creer') {
            $eleve = $this->eleves->creer($acteur, new CreateEleveDto(
                $v['nom'], $v['prenoms'], $v['sexe'], $v['date_naissance'], $v['lieu_naissance'], $v['matricule'],
                $v['classe_uuid'], 'nouvelle', false, $ligne->confirmee, $v['champs_perso'],
            ))['eleve'];
            $ligne->avant = ['uuid' => $eleve->uuid];
        } else {
            $existant = $this->eleves->voir($acteur, (string) $r->cibleUuid);
            $ligne->avant = [
                'uuid' => $existant->uuid, 'nom' => $existant->nom, 'prenoms' => $existant->prenoms, 'sexe' => $existant->sexe,
                'date_naissance' => $existant->date_naissance?->toDateString(), 'lieu_naissance' => $existant->lieu_naissance,
                'champs_perso' => $existant->champs_perso ?? [],
            ];
            $eleve = $this->eleves->modifier($acteur, $existant->uuid, new UpdateEleveDto(
                $v['nom'], $v['prenoms'], $v['sexe'], $v['date_naissance'], $v['lieu_naissance'], null,
                array_merge($existant->champs_perso ?? [], $v['champs_perso']),
            ));
        }

        $ligne->forceFill(['statut' => StatutLigneImport::Importee->value, 'action' => $r->action === 'creer' ? 'creer' : 'mettre_a_jour', 'enregistrement_id' => $eleve->id])->save();
    }
}
