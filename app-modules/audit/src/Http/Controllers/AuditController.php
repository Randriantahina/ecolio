<?php

declare(strict_types=1);

namespace Modules\Audit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Audit\Domain\Contracts\JournalAuditRepository;
use Modules\Audit\Domain\Models\JournalAudit;
use Modules\Audit\Http\Resources\JournalAuditResource;

final class AuditController extends Controller
{
    public function __construct(private readonly JournalAuditRepository $journal) {}

    /** Read-only by design: there is no route to update or delete a trail entry. */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('voir', JournalAudit::class);

        return JournalAuditResource::collection(
            $this->journal->paginateForEcole((int) $request->user()->ecole_id, 50),
        );
    }
}
