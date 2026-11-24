<?php

declare(strict_types=1);

namespace App\Filament\Support\Middleware;

use App\Domain\Platform\Models\Ecole;
use App\Domain\Tenancy\TenantContext;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bridges Filament's tenant to our TenantContext so the BelongsToEcole global scope keeps protecting
 * every query, including those of services called from Filament actions.
 */
final class BindEcoleContext
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof Ecole) {
            $this->context->forEcole($tenant->id);
        }

        return $next($request);
    }
}
