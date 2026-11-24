<?php

declare(strict_types=1);

namespace App\Domain\Audit\Contracts;

use Illuminate\Contracts\Pagination\CursorPaginator;

interface JournalAuditRepository
{
    /** Newest first. */
    public function paginateForEcole(int $ecoleId, int $perPage): CursorPaginator;
}
