<?php

declare(strict_types=1);

namespace Modules\Imports\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Imports\Application\Services\ExecutionImportService;
use Modules\Imports\Domain\Enums\StatutImport;
use Modules\Imports\Domain\Models\Import;
use Modules\Tenancy\Domain\TenantContext;
use Throwable;

/** A queued job has no request to name its school: the school travels with it (spec 6.3). */
final class ExecuterImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly int $ecoleId, public readonly int $acteurId, public readonly string $importUuid) {}

    public function handle(TenantContext $tenant, ExecutionImportService $execution): void
    {
        $tenant->runAs($this->ecoleId, function () use ($execution): void {
            $execution->executer(Utilisateur::findOrFail($this->acteurId), $this->importUuid);
        });
    }

    public function failed(Throwable $e): void
    {
        app(TenantContext::class)->runAs($this->ecoleId, function () use ($e): void {
            Import::where('uuid', $this->importUuid)->update(['statut' => StatutImport::Echec->value, 'rapport' => json_encode([['numero' => 0, 'messages' => [$e->getMessage()]]])]);
        });
    }
}
