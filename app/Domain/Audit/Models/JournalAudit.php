<?php

declare(strict_types=1);

namespace App\Domain\Audit\Models;

use App\Domain\Platform\Models\Ecole;
use Spatie\Activitylog\Models\Activity;

/**
 * Audit row stamped with its school so a directeur can only ever read their own trail.
 * Not BelongsToEcole: rows are written from jobs and platform actions where no context exists.
 */
final class JournalAudit extends Activity
{
    protected static function booted(): void
    {
        self::creating(function (self $entry): void {
            if ($entry->ecole_id !== null) {
                return;
            }

            $subject = $entry->subject;
            $entry->ecole_id = match (true) {
                $subject instanceof Ecole => $subject->id,
                isset($subject->ecole_id) => $subject->ecole_id,
                default => $entry->causer?->ecole_id,
            };
        });
    }
}
