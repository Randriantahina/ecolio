<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Eleves\Models\Eleve;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Eleve */
final class EleveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $classe = $this->relationLoaded('inscriptionActive') ? $this->inscriptionActive?->classe : null;

        return [
            'uuid' => $this->uuid,
            'matricule' => $this->matricule,
            'nom' => $this->nom,
            'prenoms' => $this->prenoms,
            'sexe' => $this->sexe,
            'date_naissance' => $this->date_naissance?->toDateString(),
            'lieu_naissance' => $this->lieu_naissance,
            'statut' => $this->statut->value,
            'date_sortie' => $this->date_sortie?->toDateString(),
            'classe' => $classe ? ClasseResource::make($classe)->resolve() : null,
        ];
    }
}
