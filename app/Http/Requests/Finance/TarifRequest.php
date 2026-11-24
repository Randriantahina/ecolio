<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Domain\Finance\Models\Tarif;
use Illuminate\Foundation\Http\FormRequest;

final class TarifRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', Tarif::class);
    }

    public function rules(): array
    {
        $creating = $this->isMethod('POST');

        return [
            'montant_ar' => ['required', 'integer', 'min:1', 'max:100000000'],
            'type_frais_uuid' => [$creating ? 'required' : 'prohibited', 'uuid'],
            'niveau_uuid' => [$creating ? 'nullable' : 'prohibited', 'uuid'],
            'classe_uuid' => [$creating ? 'nullable' : 'prohibited', 'uuid'],
            'annee_scolaire_uuid' => [$creating ? 'nullable' : 'prohibited', 'uuid'],
        ];
    }
}
