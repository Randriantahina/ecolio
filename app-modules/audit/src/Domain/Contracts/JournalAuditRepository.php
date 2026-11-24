<?php

declare(strict_types=1);

namespace Modules\Audit\Domain\Contracts;

use Illuminate\Contracts\Pagination\CursorPaginator;

interface JournalAuditRepository
{
    /** Newest first. */
    public function paginateForEcole(int $ecoleId, int $perPage): CursorPaginator;
}
