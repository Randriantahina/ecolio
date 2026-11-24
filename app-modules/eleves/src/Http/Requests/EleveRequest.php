<?php

declare(strict_types=1);

namespace Modules\Eleves\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Eleves\Application\DTOs\CreateEleveDto;
use Modules\Eleves\Application\DTOs\UpdateEleveDto;
use Modules\Eleves\Domain\Models\Eleve;

final class EleveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', Eleve::class);
    }

    public function rules(): array
    {
        $creating = $this->isMethod('POST');

        return [
            'nom' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'prenoms' => ['nullable', 'string', 'max:255'],
            'sexe' => ['nullable', Rule::in(['F', 'M'])],
            'date_naissance' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'matricule' => ['nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9._\\/-]+$/'],
            'classe_uuid' => [$creating ? 'nullable' : 'prohibited', 'uuid'],
            'type_inscription' => ['sometimes', Rule::in(['nouvelle', 'reinscription', 'transfert'])],
            'est_redoublant' => ['sometimes', 'boolean'],
            'confirmer_doublon' => ['sometimes', 'boolean'],
        ];
    }

    public function toCreateDto(): CreateEleveDto
    {
        $d = $this->validated();

        return new CreateEleveDto(
            nom: $d['nom'],
            prenoms: $d['prenoms'] ?? null,
            sexe: $d['sexe'] ?? null,
            dateNaissance: $d['date_naissance'] ?? null,
            lieuNaissance: $d['lieu_naissance'] ?? null,
            matricule: $d['matricule'] ?? null,
            classeUuid: $d['classe_uuid'] ?? null,
            typeInscription: $d['type_inscription'] ?? 'nouvelle',
            estRedoublant: (bool) ($d['est_redoublant'] ?? false),
            confirmerDoublon: (bool) ($d['confirmer_doublon'] ?? false),
        );
    }

    public function toUpdateDto(): UpdateEleveDto
    {
        $d = $this->validated();

        return new UpdateEleveDto(
            nom: $d['nom'] ?? null,
            prenoms: $d['prenoms'] ?? null,
            sexe: $d['sexe'] ?? null,
            dateNaissance: $d['date_naissance'] ?? null,
            lieuNaissance: $d['lieu_naissance'] ?? null,
            matricule: $d['matricule'] ?? null,
        );
    }
}
