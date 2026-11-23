<?php

declare(strict_types=1);

namespace App\Http\Controllers\Audit;

use App\Domain\Audit\Contracts\JournalAuditRepository;
use App\Domain\Audit\Models\JournalAudit;
use App\Http\Controllers\Controller;
use App\Http\Resources\JournalAuditResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

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
