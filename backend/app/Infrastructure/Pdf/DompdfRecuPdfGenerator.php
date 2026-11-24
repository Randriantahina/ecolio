<?php

declare(strict_types=1);

namespace App\Infrastructure\Pdf;

use App\Domain\Finance\Contracts\RecuPdfGenerator;
use Barryvdh\DomPDF\Facade\Pdf;

final class DompdfRecuPdfGenerator implements RecuPdfGenerator
{
    public function generate(array $recu): string
    {
        // A5 portrait: a receipt is handed over at the counter, half a sheet is enough and saves paper.
        return Pdf::loadView('pdf.recu', ['recu' => $recu])->setPaper('a5')->output();
    }
}
