<?php

declare(strict_types=1);

namespace Modules\Dynamique\Domain\Enums;

/** What a custom field is attached to: its values live in the JSON `champs_perso` column of that entity. */
enum EntiteChamp: string
{
    case Eleve = 'eleve';
    case Tuteur = 'tuteur';
}
