<?php

declare(strict_types=1);

namespace Modules\Scolarite\Domain\Exceptions;

use DomainException;

final class InvalidAnneeScolaireException extends DomainException
{
    public static function endsBeforeStart(): self
    {
        return new self('A school year must end after it starts.');
    }
}
