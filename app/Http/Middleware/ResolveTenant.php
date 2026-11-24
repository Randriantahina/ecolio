<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
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
