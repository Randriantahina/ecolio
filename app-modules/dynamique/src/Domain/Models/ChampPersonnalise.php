<?php

declare(strict_types=1);

namespace Modules\Dynamique\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Dynamique\Domain\Enums\EntiteChamp;
use Modules\Dynamique\Domain\Enums\TypeChamp;
use Modules\Tenancy\Domain\BelongsToEcole;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/** Definition of a school-specific column (spec section 3). The value never has a structure of its own. */
final class ChampPersonnalise extends Model
{
    use BelongsToEcole, HasUuids, LogsActivity;

    protected $table = 'champs_personnalises';

    protected $fillable = ['libelle', 'obligatoire', 'ordre', 'options'];

    protected $attributes = ['obligatoire' => false, 'actif' => true, 'ordre' => 0];

    protected function casts(): array
    {
        return [
            'entite' => EntiteChamp::class,
            'type' => TypeChamp::class,
            'options' => 'array',
            'obligatoire' => 'boolean',
            'actif' => 'boolean',
            'desactive_le' => 'datetime',
        ];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['libelle', 'type', 'options', 'obligatoire', 'actif'])->logOnlyDirty()->useLogName('champs');
    }
}
