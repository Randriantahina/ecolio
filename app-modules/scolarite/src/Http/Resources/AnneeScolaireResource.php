<?php

declare(strict_types=1);

namespace Modules\Scolarite\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Scolarite\Domain\Models\AnneeScolaire;

/** @mixin AnneeScolaire */
final class AnneeScolaireResource extends JsonResource
{
    /** Numeric ids stay server-side; clients only know uuids. */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'libelle' => $this->libelle,
            'date_debut' => $this->date_debut->toDateString(),
            'date_fin' => $this->date_fin->toDateString(),
            'est_courante' => $this->est_courante,
        ];
    }
}
