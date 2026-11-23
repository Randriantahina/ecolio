<?php

declare(strict_types=1);

namespace App\Domain\Platform\Models;

use Database\Factories\EcoleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/** The tenant itself: not school-scoped, it is what the scope points to. */
final class Ecole extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $table = 'ecoles';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'format_matricule' => 'array',
            'regles_calcul' => 'array',
            'parametres' => 'array',
            'actif' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nom', 'adresse', 'telephone', 'email', 'parametres', 'actif'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('ecoles');
    }

    /** Public identifier lives in `uuid`; the numeric id stays internal. */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function newFactory(): EcoleFactory
    {
        return EcoleFactory::new();
    }
}
