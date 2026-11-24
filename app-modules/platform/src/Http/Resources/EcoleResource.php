<?php

declare(strict_types=1);

namespace Modules\Platform\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Platform\Domain\Models\Ecole;

/** @mixin Ecole */
final class EcoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'nom' => $this->nom,
            'slug' => $this->slug,
            'adresse' => $this->adresse,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'devise' => $this->devise,
            'fuseau' => $this->fuseau,
            'mention_legale' => $this->parametres['mention_legale'] ?? null,
            'signature_recu' => $this->parametres['signature_recu'] ?? null,
        ];
    }
}
