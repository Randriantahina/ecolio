<?php

declare(strict_types=1);

namespace App\Http\Requests\Eleves;

use App\Domain\Eleves\Models\Eleve;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
