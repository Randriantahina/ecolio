<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Finance\Models\Tarif;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tarif */
final class TarifResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'montant_ar' => $this->montant_ar,
            'type_frais' => $this->whenLoaded('typeFrais', fn () => TypeFraisResource::make($this->typeFrais)->resolve()),
            'niveau_id' => $this->niveau_id !== null,
            'classe_id' => $this->classe_id !== null,
        ];
    }
}
