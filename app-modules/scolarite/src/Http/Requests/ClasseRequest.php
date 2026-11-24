<?php

declare(strict_types=1);

namespace Modules\Scolarite\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Scolarite\Domain\Models\Classe;

final class ClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', Classe::class);
    }

    public function rules(): array
    {
        $creating = $this->isMethod('POST');

        return [
            'nom' => [$creating ? 'required' : 'sometimes', 'string', 'max:60'],
            'niveau_uuid' => [$creating ? 'required' : 'prohibited', 'uuid'],
            'annee_scolaire_uuid' => ['nullable', 'uuid', $creating ? 'sometimes' : 'prohibited'],
            'effectif_max' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }
}
