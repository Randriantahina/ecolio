<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Contracts;

interface RecuPdfGenerator
{
    /**
     * @param  array<string, mixed>  $recu  The receipt as exposed by the API (RG-R4 fields).
     * @return string Binary PDF content.
     */
    public function generate(array $recu): string;
}
