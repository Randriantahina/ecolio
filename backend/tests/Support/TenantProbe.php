<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Tenancy\BelongsToEcole;
use Illuminate\Database\Eloquent\Model;

/**
 * Throwaway tenant-scoped model: lets the trait be tested without depending on a real business
 * model, so a broken Eleve migration can never hide a broken tenancy mechanism.
 */
final class TenantProbe extends Model
{
    use BelongsToEcole;

    protected $table = 'tenant_probes';

    protected $guarded = [];

    public $timestamps = false;
}
