<?php

declare(strict_types=1);

namespace Modules\Imports\Application\DTOs;

/** Outcome of judging one staged row: clean values, errors, and what running it would do. */
final readonly class ResultatLigne
{
    /** Errors a human can accept in one click instead of fixing a cell. */
    public const CONFIRMABLES = ['doublon', 'a_confirmer'];

    /**
     * @param  array<string, mixed>  $valeurs  matricule, nom, prenoms, sexe, date_naissance, lieu_naissance, classe_uuid, champs_perso
     * @param  list<array{champ: string, code: string, message: string}>  $erreurs
     * @param  'creer'|'mettre_a_jour'  $action
     */
    public function __construct(
        public array $valeurs,
        public array $erreurs,
        public string $action,
        public ?int $cibleId = null,
        public ?string $cibleUuid = null,
    ) {}

    public function estBloquant(bool $confirmee): bool
    {
        foreach ($this->erreurs as $erreur) {
            if (! in_array($erreur['code'], self::CONFIRMABLES, true) || ! $confirmee) {
                return true;
            }
        }

        return false;
    }
}
