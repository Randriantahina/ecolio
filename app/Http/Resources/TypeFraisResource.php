<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Finance\Models\TypeFrais;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TypeFrais */
final class TypeFraisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'nom' => $this->nom,
            'periodicite' => $this->periodicite->value,
            'est_obligatoire' => $this->est_obligatoire,
            'actif' => $this->actif,
        ];
    }
}
