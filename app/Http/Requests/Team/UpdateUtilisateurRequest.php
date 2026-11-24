<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use App\Application\Identity\DTOs\UpdateUtilisateurDto;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\Utilisateur;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
