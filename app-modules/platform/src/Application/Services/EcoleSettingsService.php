<?php

declare(strict_types=1);

namespace Modules\Platform\Application\Services;

use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Platform\Application\DTOs\UpdateEcoleDto;
use Modules\Platform\Domain\Contracts\EcoleRepository;
use Modules\Platform\Domain\Models\Ecole;

final class EcoleSettingsService
{
    public function __construct(private readonly EcoleRepository $ecoles) {}

    /** The school always comes from the actor, never from a request parameter. */
    public function voir(Utilisateur $acteur): Ecole
    {
        return $this->ecoles->findOrFail((int) $acteur->ecole_id);
    }

    public function modifier(Utilisateur $acteur, UpdateEcoleDto $dto): Ecole
    {
        $ecole = $this->voir($acteur);

        $ecole->fill(array_filter([
            'nom' => $dto->nom,
            'adresse' => $dto->adresse,
            'telephone' => $dto->telephone,
            'email' => $dto->email,
        ], fn ($value) => $value !== null));

        // Receipt texts live in the JSON settings; merging keeps keys the client did not send.
        $parametres = $ecole->parametres ?? [];
        if ($dto->mentionLegale !== null) {
            $parametres['mention_legale'] = $dto->mentionLegale;
        }
        if ($dto->signatureRecu !== null) {
            $parametres['signature_recu'] = $dto->signatureRecu;
        }
        $ecole->parametres = $parametres;

        $this->ecoles->save($ecole);

        return $ecole;
    }
}
