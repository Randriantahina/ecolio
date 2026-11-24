<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Models;

use App\Domain\Tenancy\BelongsToEcole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

final class AnneeScolaire extends Model
{
    use BelongsToEcole, HasUuids, LogsActivity;

    protected $table = 'annees_scolaires';

    protected $fillable = ['libelle', 'date_debut', 'date_fin', 'est_courante'];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'est_courante' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['libelle', 'date_debut', 'date_fin', 'est_courante'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('annees_scolaires');
    }

    /** Public identifier lives in `uuid`; the numeric id stays internal. */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
