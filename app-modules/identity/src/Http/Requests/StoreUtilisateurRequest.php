<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Identity\Application\DTOs\CreateUtilisateurDto;
use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Domain\Models\Utilisateur;

final class StoreUtilisateurRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization runs before validation so a forbidden role gets 403, not a hint about the schema.
        return $this->user()->can('gererEquipe', Utilisateur::class);
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'required_without:telephone', 'email', 'max:255', 'unique:utilisateurs,email'],
            'telephone' => ['nullable', 'required_without:email', 'string', 'max:30', 'unique:utilisateurs,telephone'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'role' => ['required', Rule::in(self::assignableRoles())],
        ];
    }

    /** @return list<string> */
    public static function assignableRoles(): array
    {
        return collect(Role::cases())
            ->reject(fn (Role $role) => $role === Role::SuperAdmin)
            ->map(fn (Role $role) => $role->value)
            ->values()
            ->all();
    }

    public function toDto(): CreateUtilisateurDto
    {
        $data = $this->validated();

        return new CreateUtilisateurDto(
            nom: $data['nom'],
            email: $data['email'] ?? null,
            telephone: $data['telephone'] ?? null,
            password: $data['password'],
            role: Role::from($data['role']),
        );
    }
}
