<?php

declare(strict_types=1);

namespace Modules\Dynamique\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Dynamique\Domain\Enums\EntiteVue;
use Modules\Tenancy\Domain\BelongsToEcole;

/** A saved table view (spec US3.4): personal, or shared by the directeur with the school. */
final class VueTableau extends Model
{
    use BelongsToEcole, HasUuids;

    protected $table = 'vues_tableaux';

    protected $fillable = ['nom', 'etat', 'est_partagee', 'est_par_defaut'];

    protected function casts(): array
    {
        return ['entite' => EntiteVue::class, 'etat' => 'array', 'est_partagee' => 'boolean', 'est_par_defaut' => 'boolean'];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
