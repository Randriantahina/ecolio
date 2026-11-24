<?php

declare(strict_types=1);

namespace Modules\Absences\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Eleves\Domain\Models\Eleve;
use Modules\Scolarite\Domain\Models\Classe;
use Modules\Tenancy\Domain\BelongsToEcole;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

final class Absence extends Model
{
    use BelongsToEcole, LogsActivity;

    protected $table = 'absences';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['date' => 'date', 'justifiee' => 'boolean'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        // RG-P5: removing an absence is a plain delete, but it must leave a trace.
        return LogOptions::defaults()
            ->logOnly(['date', 'periode', 'justifiee', 'motif'])
            ->logOnlyDirty()
            ->useLogName('absences');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }
}
