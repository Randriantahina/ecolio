<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Identity\Application\DTOs\UpdateUtilisateurDto;
use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Domain\Models\Utilisateur;

final class UpdateUtilisateurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gererEquipe', Utilisateur::class);
    }

    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'string', 'max:255'],
            'actif' => ['sometimes', 'boolean'],
            'role' => ['sometimes', Rule::in(StoreUtilisateurRequest::assignableRoles())],
        ];
    }

    public function toDto(): UpdateUtilisateurDto
    {
        $data = $this->validated();

        return new UpdateUtilisateurDto(
            nom: $data['nom'] ?? null,
            actif: array_key_exists('actif', $data) ? (bool) $data['actif'] : null,
            role: isset($data['role']) ? Role::from($data['role']) : null,
        );
    }
}
