<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Modules\Tenancy\Domain\Exceptions\TenantNotResolvedException;

final class EcoleScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if ($context->isPlatform()) {
            return;
        }

        if (! $context->isResolved()) {
            throw TenantNotResolvedException::create();
        }

        $builder->where($model->qualifyColumn('ecole_id'), $context->ecoleId());
    }
}
