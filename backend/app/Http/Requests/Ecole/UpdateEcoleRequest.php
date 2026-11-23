<?php

declare(strict_types=1);

namespace App\Http\Requests\Ecole;

use App\Application\Platform\DTOs\UpdateEcoleDto;
use App\Domain\Platform\Models\Ecole;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateEcoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('modifier', Ecole::class);
    }

    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'adresse' => ['sometimes', 'nullable', 'string', 'max:255'],
            'telephone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'mention_legale' => ['sometimes', 'nullable', 'string', 'max:500'],
            'signature_recu' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function toDto(): UpdateEcoleDto
    {
        $data = $this->validated();

        return new UpdateEcoleDto(
            nom: $data['nom'] ?? null,
            adresse: $data['adresse'] ?? null,
            telephone: $data['telephone'] ?? null,
            email: $data['email'] ?? null,
            mentionLegale: $data['mention_legale'] ?? null,
            signatureRecu: $data['signature_recu'] ?? null,
        );
    }
}
