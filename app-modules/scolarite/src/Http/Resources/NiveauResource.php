<?php

declare(strict_types=1);

namespace Modules\Scolarite\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Scolarite\Domain\Models\Niveau;

/** @mixin Niveau */
final class NiveauResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['uuid' => $this->uuid, 'nom' => $this->nom, 'cycle' => $this->cycle->value, 'ordre' => $this->ordre];
    }
}
