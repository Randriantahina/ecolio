<?php

declare(strict_types=1);

namespace Modules\Eleves\Infrastructure;

use Modules\Dynamique\Domain\Contracts\LecteurValeursChamps;
use Modules\Dynamique\Domain\Enums\EntiteChamp;
use Modules\Eleves\Domain\Models\Eleve;

/** Lets the Dynamique module check a type change (RG-D3) against the stored pupil values. */
final class ChampsPersoLecteur implements LecteurValeursChamps
{
    public function supporte(EntiteChamp $entite): bool
    {
        return $entite === EntiteChamp::Eleve;
    }

    public function valeurs(int $ecoleId, EntiteChamp $entite, string $cle): array
    {
        $valeurs = [];
        Eleve::query()->where('ecole_id', $ecoleId)->whereNotNull('champs_perso')->get(['matricule', 'nom', 'champs_perso'])
            ->each(function (Eleve $e) use (&$valeurs, $cle): void {
                $v = $e->champs_perso[$cle] ?? null;
                if ($v !== null && $v !== '') {
                    $valeurs["{$e->matricule} {$e->nom}"] = $v;
                }
            });

        return $valeurs;
    }
}
