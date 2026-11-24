<?php

declare(strict_types=1);

namespace Modules\Eleves\Domain\Exceptions;

use DomainException;
use Modules\Eleves\Domain\Models\Eleve;

/** RG-E4: blocking warning; the caller may confirm and retry. */
final class DuplicateEleveException extends DomainException
{
    public function __construct(public readonly Eleve $existant)
    {
        parent::__construct('A pupil with the same name and birth date already exists.');
    }
}
