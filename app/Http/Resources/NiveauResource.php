<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Scolarite\Models\Niveau;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Niveau */
final class NiveauResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['uuid' => $this->uuid, 'nom' => $this->nom, 'cycle' => $this->cycle->value, 'ordre' => $this->ordre];
    }
}
