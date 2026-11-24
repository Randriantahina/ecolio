<?php

declare(strict_types=1);

namespace Modules\Scolarite\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Scolarite\Domain\Enums\Cycle;
use Modules\Scolarite\Domain\Models\Niveau;

/** Create and update share rules; `sometimes` only applies on update (PATCH). */
final class NiveauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', Niveau::class);
    }

    public function rules(): array
    {
        $required = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'nom' => [$required, 'string', 'max:60',
                Rule::unique('niveaux', 'nom')->where('ecole_id', $this->user()->ecole_id)->ignore($this->route('uuid'), 'uuid')],
            'cycle' => ['sometimes', Rule::enum(Cycle::class)],
            'ordre' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ];
    }
}
