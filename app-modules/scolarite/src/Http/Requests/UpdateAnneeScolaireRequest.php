<?php

declare(strict_types=1);

namespace Modules\Scolarite\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Scolarite\Application\DTOs\UpdateAnneeScolaireDto;
use Modules\Scolarite\Domain\Models\AnneeScolaire;

final class UpdateAnneeScolaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', AnneeScolaire::class);
    }

    public function rules(): array
    {
        return [
            'libelle' => ['sometimes', 'string', 'max:50',
                Rule::unique('annees_scolaires', 'libelle')
                    ->where('ecole_id', $this->user()->ecole_id)
                    ->ignore($this->route('uuid'), 'uuid')],
            'date_debut' => ['sometimes', 'date_format:Y-m-d'],
            'date_fin' => ['sometimes', 'date_format:Y-m-d'],
        ];
    }

    public function toDto(): UpdateAnneeScolaireDto
    {
        $data = $this->validated();

        return new UpdateAnneeScolaireDto(
            libelle: $data['libelle'] ?? null,
            dateDebut: $data['date_debut'] ?? null,
            dateFin: $data['date_fin'] ?? null,
        );
    }
}
