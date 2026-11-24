<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain;

use Modules\Tenancy\Domain\Exceptions\TenantNotResolvedException;

/**
 * Holds which school the current request/job acts for. Registered as a scoped singleton so
 * state never leaks between requests (Octane, queue workers).
 */
final class TenantContext
{
    private ?int $ecoleId = null;

    private bool $platform = false;

    public function forEcole(int $ecoleId): void
    {
        $this->ecoleId = $ecoleId;
        $this->platform = false;
    }

    /** Super admins act above schools; they see every row but must name a school to create one. */
    public function asPlatform(): void
    {
        $this->ecoleId = null;
        $this->platform = true;
    }

    public function clear(): void
    {
        $this->ecoleId = null;
        $this->platform = false;
    }

    public function isResolved(): bool
    {
        return $this->platform || $this->ecoleId !== null;
    }

    public function isPlatform(): bool
    {
        return $this->platform;
    }

    public function ecoleId(): ?int
    {
        return $this->ecoleId;
    }

    public function requireEcoleId(): int
    {
        return $this->ecoleId ?? throw TenantNotResolvedException::create();
    }

    /** Jobs and seeders switch school this way; the finally block guarantees the previous context returns. */
    public function runAs(int $ecoleId, callable $callback): mixed
    {
        [$previousId, $previousPlatform] = [$this->ecoleId, $this->platform];
        $this->forEcole($ecoleId);

        try {
            return $callback();
        } finally {
            $this->ecoleId = $previousId;
            $this->platform = $previousPlatform;
        }
    }
}
