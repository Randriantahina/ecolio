<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Finance\Domain\Contracts\CompteurRepository;

/**
 * Query builder rather than a scoped model: the school is passed explicitly because the
 * caller may be a queued job with no HTTP tenant context.
 */
final class EloquentCompteurRepository implements CompteurRepository
{
    public function nextValue(int $ecoleId, string $cle): int
    {
        if ($cle === '') {
            throw new InvalidArgumentException('A counter key cannot be empty.');
        }

        return DB::transaction(function () use ($ecoleId, $cle): int {
            // Create-if-missing without failing: a unique violation would abort the whole
            // transaction on PostgreSQL, whereas two cashiers can legitimately race here.
            DB::table('compteurs')->insertOrIgnore(['ecole_id' => $ecoleId, 'cle' => $cle, 'valeur' => 0]);

            // RG-R1: the row lock serialises concurrent cashiers, so two payments can never
            // read the same value. The lock lasts until the surrounding transaction ends.
            $current = (int) DB::table('compteurs')
                ->where('ecole_id', $ecoleId)
                ->where('cle', $cle)
                ->lockForUpdate()
                ->value('valeur');

            $next = $current + 1;

            DB::table('compteurs')
                ->where('ecole_id', $ecoleId)
                ->where('cle', $cle)
                ->update(['valeur' => $next]);

            return $next;
        });
    }
}
