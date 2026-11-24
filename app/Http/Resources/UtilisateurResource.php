<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Identity\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
