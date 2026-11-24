<?php

declare(strict_types=1);

namespace App\Domain\Tenancy;

use App\Domain\Platform\Models\Ecole;
use App\Domain\Tenancy\Exceptions\CrossTenantWriteException;
use App\Domain\Tenancy\Exceptions\TenantNotResolvedException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Makes a model school-private (spec 6.3). Reads are scoped by EcoleScope; writes get their
 * ecole_id from the context. Deliberate exception to "no container in Domain": Eloquent boot
 * hooks are the only integration point for tenancy and cannot receive constructor injection.
 */
trait BelongsToEcole
{
    public static function bootBelongsToEcole(): void
    {
        static::addGlobalScope(new EcoleScope);

        static::creating(function ($model): void {
            $context = app(TenantContext::class);
            $explicit = $model->ecole_id;

            if ($explicit !== null) {
                // A school-scoped caller must never plant a row in someone else's school.
                if ($context->ecoleId() !== null && (int) $explicit !== $context->ecoleId()) {
                    throw CrossTenantWriteException::create($context->ecoleId(), (int) $explicit);
                }

                return;
            }

            if ($context->ecoleId() === null) {
                throw TenantNotResolvedException::create();
            }

            $model->ecole_id = $context->ecoleId();
        });
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class);
    }
}
