<?php

declare(strict_types=1);

namespace App\Http\Navigation;

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\Utilisateur;

/**
 * The server decides which entries a role may see, so the menu can never promise a page
 * that the policies would refuse. Keys map to labels and icons in the front layout.
 */
final class MenuPrincipal
{
    /** @return list<string> */
    public function pour(Utilisateur $utilisateur): array
    {
        $roles = $utilisateur->getRoleNames();

        if ($roles->contains(Role::Directeur->value)) {
            return ['accueil'];
        }

        return ['accueil'];
    }
}
