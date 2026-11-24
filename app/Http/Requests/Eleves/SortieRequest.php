<?php

declare(strict_types=1);

namespace App\Http\Requests\Eleves;

use App\Domain\Eleves\Models\Eleve;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SortieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', Eleve::class);
    }

    public function rules(): array
    {
        return [
            'statut' => ['required', Rule::in(['sorti', 'transfere', 'abandon', 'diplome'])],
            'date_sortie' => ['required', 'date_format:Y-m-d'],
            'motif_sortie' => ['nullable', 'string', 'max:100'],
            'commentaire_sortie' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
