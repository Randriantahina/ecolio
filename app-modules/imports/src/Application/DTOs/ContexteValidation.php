<?php

declare(strict_types=1);

namespace Modules\Imports\Application\DTOs;

use Modules\Dynamique\Domain\Enums\TypeChamp;
use Modules\Imports\Domain\Enums\ModeImport;

/** Everything a row needs to be judged, loaded once per validation pass instead of once per row. */
final readonly class ContexteValidation
{
    /**
     * @param  array<string, string>  $classes  normalised class name => class uuid
     * @param  array<string, TypeChamp>  $champs  custom field key => type
     * @param  array<string, int>  $matriculesFichier  matricule => how many rows of the file carry it
     */
    public function __construct(
        public ModeImport $mode,
        public array $classes,
        public array $champs,
        public array $matriculesFichier,
    ) {}
}
