<?php

declare(strict_types=1);

namespace Modules\Imports\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Imports\Domain\Enums\EntiteImport;
use Modules\Imports\Domain\Enums\ModeImport;
use Modules\Imports\Domain\Enums\StatutImport;
use Modules\Tenancy\Domain\BelongsToEcole;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

final class Import extends Model
{
    use BelongsToEcole, HasUuids, LogsActivity;

    /** RG-M5 [Hypothèse]: ceiling per import in v1. */
    public const MAX_LIGNES = 5000;

    /** Spec 4.1 step 5 [Hypothèse]: a finished batch can be rolled back for 7 days. */
    public const JOURS_ANNULATION = 7;

    protected $table = 'imports';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'entite' => EntiteImport::class, 'mode' => ModeImport::class, 'statut' => StatutImport::class,
            'mapping' => 'array', 'rapport' => 'array', 'termine_le' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(ImportLigne::class)->orderBy('numero');
    }

    public function peutEtreAnnule(): bool
    {
        return $this->statut === StatutImport::Termine && $this->termine_le !== null
            && $this->termine_le->greaterThan(now()->subDays(self::JOURS_ANNULATION));
    }

    public function getActivitylogOptions(): LogOptions
    {
        // Spec section 5: imports are audited.
        return LogOptions::defaults()->logOnly(['statut', 'entite', 'mode', 'nom_original', 'total', 'crees', 'mis_a_jour'])
            ->logOnlyDirty()->useLogName('imports');
    }
}
