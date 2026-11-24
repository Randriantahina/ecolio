<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Domain\Finance\Enums\Periodicite;
use App\Domain\Finance\Models\TypeFrais;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TypeFraisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', TypeFrais::class);
    }

    public function rules(): array
    {
        $r = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'nom' => [$r, 'string', 'max:100',
                Rule::unique('types_frais', 'nom')->where('ecole_id', $this->user()->ecole_id)->ignore($this->route('uuid'), 'uuid')],
            'periodicite' => [$r, Rule::enum(Periodicite::class)],
            'est_obligatoire' => ['sometimes', 'boolean'],
            'actif' => ['sometimes', 'boolean'],
        ];
    }
}
