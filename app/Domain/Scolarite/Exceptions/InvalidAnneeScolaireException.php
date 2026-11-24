<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Exceptions;

use DomainException;

final class InvalidAnneeScolaireException extends DomainException
{
    public static function endsBeforeStart(): self
    {
        return new self('A school year must end after it starts.');
    }
}
