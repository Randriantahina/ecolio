<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Finance\Domain\Enums\Periodicite;
use Modules\Finance\Domain\Models\TypeFrais;

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
