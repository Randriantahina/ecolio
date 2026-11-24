<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Modules\Tenancy\Domain\BelongsToEcole;

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
