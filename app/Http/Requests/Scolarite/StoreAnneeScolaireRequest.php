<?php

declare(strict_types=1);

namespace App\Http\Requests\Scolarite;

use App\Application\Scolarite\DTOs\CreateAnneeScolaireDto;
use App\Domain\Scolarite\Models\AnneeScolaire;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAnneeScolaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization runs before validation so a forbidden role gets 403, not a hint about the schema.
        return $this->user()->can('manage', AnneeScolaire::class);
    }

    public function rules(): array
    {
        return [
            // Scoped to the actor's school: the same label may exist in another school.
            'libelle' => ['required', 'string', 'max:50',
                Rule::unique('annees_scolaires', 'libelle')->where('ecole_id', $this->user()->ecole_id)],
            'date_debut' => ['required', 'date_format:Y-m-d'],
            'date_fin' => ['required', 'date_format:Y-m-d', 'after:date_debut'],
            'est_courante' => ['sometimes', 'boolean'],
        ];
    }

    public function toDto(): CreateAnneeScolaireDto
    {
        $data = $this->validated();

        return new CreateAnneeScolaireDto(
            libelle: $data['libelle'],
            dateDebut: $data['date_debut'],
            dateFin: $data['date_fin'],
            estCourante: (bool) ($data['est_courante'] ?? false),
        );
    }
}
