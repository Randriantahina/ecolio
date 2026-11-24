<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Identity\Domain\Models\Utilisateur;

/** @mixin Utilisateur */
final class UtilisateurResource extends JsonResource
{
    /** Numeric ids and the hash stay server-side; clients only know uuids. */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'nom' => $this->nom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'langue' => $this->langue,
            'roles' => $this->getRoleNames()->values()->all(),
            'ecole' => $this->ecole ? ['uuid' => $this->ecole->uuid, 'nom' => $this->ecole->nom] : null,
        ];
    }
}
