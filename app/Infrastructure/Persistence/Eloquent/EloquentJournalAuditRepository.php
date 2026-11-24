<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Audit\Contracts\JournalAuditRepository;
use App\Domain\Audit\Models\JournalAudit;
use Illuminate\Contracts\Pagination\CursorPaginator;

final class EloquentJournalAuditRepository implements JournalAuditRepository
{
    public function paginateForEcole(int $ecoleId, int $perPage): CursorPaginator
    {
        return JournalAudit::query()
            ->where('ecole_id', $ecoleId)
            ->with(['causer', 'subject'])
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }
}
