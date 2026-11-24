<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Persistence\Eloquent;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\Audit\Domain\Contracts\JournalAuditRepository;
use Modules\Audit\Domain\Models\JournalAudit;

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
