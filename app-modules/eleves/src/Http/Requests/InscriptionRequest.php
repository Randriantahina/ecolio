<?php

declare(strict_types=1);

namespace Modules\Eleves\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Eleves\Domain\Models\Eleve;

final class InscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', Eleve::class);
    }

    public function rules(): array
    {
        return [
            'classe_uuid' => ['required', 'uuid'],
            'type' => ['sometimes', Rule::in(['nouvelle', 'reinscription', 'transfert'])],
            'est_redoublant' => ['sometimes', 'boolean'],
        ];
    }
}
