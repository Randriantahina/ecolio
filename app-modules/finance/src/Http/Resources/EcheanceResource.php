<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Finance\Domain\Models\Echeance;

/** @mixin Echeance */
final class EcheanceResource extends JsonResource
{
    public function __construct($resource, private readonly int $paye = 0)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $paye = min($this->paye, $this->net()->amount());

        return [
            'uuid' => $this->uuid,
            'libelle' => $this->libelle,
            'periode' => $this->periode,
            'montant_ar' => $this->montant_ar,
            'remise_ar' => $this->remise_ar,
            'paye_ar' => $paye,
            'solde_ar' => $this->net()->amount() - $paye,
            'statut' => $this->statut->value,
        ];
    }
}
