<?php

declare(strict_types=1);

namespace App\Application\Identity\Services;

use App\Application\Identity\DTOs\CreateUtilisateurDto;
use App\Application\Identity\DTOs\UpdateUtilisateurDto;
use App\Domain\Identity\Contracts\UtilisateurRepository;
use App\Domain\Identity\Models\Utilisateur;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

final class TeamService
{
    public function __construct(private readonly UtilisateurRepository $utilisateurs) {}

    /** @return CursorPaginator<Utilisateur> */
    public function lister(Utilisateur $acteur, int $perPage = 50): CursorPaginator
    {
        return $this->utilisateurs->paginateForEcole((int) $acteur->ecole_id, $perPage);
    }

    public function creer(Utilisateur $acteur, CreateUtilisateurDto $dto): Utilisateur
    {
        return DB::transaction(function () use ($acteur, $dto): Utilisateur {
            $membre = new Utilisateur([
                'nom' => $dto->nom,
                'email' => $dto->email,
                'telephone' => $dto->telephone,
                'mot_de_passe' => $dto->password,
            ]);
            // Always the actor's school, never a client-supplied one: this is the tenancy guarantee for writes.
            $membre->ecole_id = $acteur->ecole_id;

            $this->utilisateurs->save($membre);
            $membre->assignRole($dto->role->value);
            $this->journaliserRole($acteur, $membre, $dto->role->value);

            return $membre->load('ecole', 'roles');
        });
    }

    public function modifier(Utilisateur $acteur, string $uuid, UpdateUtilisateurDto $dto): Utilisateur
    {
        return DB::transaction(function () use ($acteur, $uuid, $dto): Utilisateur {
            $membre = $this->utilisateurs->findInEcoleOrFail($uuid, (int) $acteur->ecole_id);

            if ($dto->nom !== null) {
                $membre->nom = $dto->nom;
            }

            if ($dto->actif !== null) {
                $membre->actif = $dto->actif;
            }

            $this->utilisateurs->save($membre);

            if ($dto->role !== null) {
                $membre->syncRoles([$dto->role->value]);
                $this->journaliserRole($acteur, $membre, $dto->role->value);
            }

            // A deactivated account must lose access now, not when its 30-day token expires.
            if ($dto->actif === false) {
                $membre->tokens()->delete();
            }

            return $membre->load('ecole', 'roles');
        });
    }

    /** Role changes are the most sensitive team event and are not model attributes, so they are logged explicitly. */
    private function journaliserRole(Utilisateur $acteur, Utilisateur $membre, string $role): void
    {
        activity('utilisateurs')
            ->performedOn($membre)
            ->causedBy($acteur)
            ->event('role_changed')
            ->withProperties(['role' => $role])
            ->log('role_changed');
    }
}
