<?php

declare(strict_types=1);

namespace App\Domain\Eleves\Exceptions;

use App\Domain\Eleves\Models\Eleve;
use DomainException;

/** RG-E4: blocking warning; the caller may confirm and retry. */
final class DuplicateEleveException extends DomainException
{
    public function __construct(public readonly Eleve $existant)
    {
        parent::__construct('A pupil with the same name and birth date already exists.');
    }
}
