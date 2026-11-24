<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Finance\Domain\Models\Tarif;

/** @mixin Tarif */
final class TarifResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'montant_ar' => $this->montant_ar,
            'type_frais' => $this->whenLoaded('typeFrais', fn () => TypeFraisResource::make($this->typeFrais)->resolve()),
            'niveau' => $this->whenLoaded('niveau', fn () => $this->niveau ? ['uuid' => $this->niveau->uuid, 'nom' => $this->niveau->nom] : null),
            'classe' => $this->whenLoaded('classe', fn () => $this->classe ? ['uuid' => $this->classe->uuid, 'nom' => $this->classe->nom] : null),
        ];
    }
}
