<?php

declare(strict_types=1);

namespace Modules\Tenancy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Tenancy\Domain\TenantContext;
use Symfony\Component\HttpFoundation\Response;

/** Must run after authentication: the school comes from the authenticated user, never from the request. */
final class ResolveTenant
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $user->estSuperAdmin()
            ? $this->tenant->asPlatform()
            : $this->tenant->forEcole((int) $user->ecole_id);

        return $next($request);
    }
}
