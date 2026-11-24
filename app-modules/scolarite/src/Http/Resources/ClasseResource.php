<?php

declare(strict_types=1);

namespace Modules\Scolarite\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Scolarite\Domain\Models\Classe;

/** @mixin Classe */
final class ClasseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'nom' => $this->nom,
            'effectif_max' => $this->effectif_max,
            'niveau' => $this->whenLoaded('niveau', fn () => NiveauResource::make($this->niveau)->resolve()),
        ];
    }
}
