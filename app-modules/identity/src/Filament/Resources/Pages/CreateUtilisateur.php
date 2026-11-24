<?php

declare(strict_types=1);

namespace Modules\Identity\Filament\Resources\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Application\Services\InvitationService;
use Modules\Identity\Domain\Enums\CanalInvitation;
use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Filament\Resources\UtilisateurResource;

final class CreateUtilisateur extends CreateRecord
{
    protected static string $resource = UtilisateurResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(InvitationService::class)->inviter(
                auth()->user(), $data['nom'], $data['email'] ?? null, $data['telephone'] ?? null,
                Role::from($data['role']), CanalInvitation::from($data['canal']), Filament::getPanel('ecole')->getLoginUrl(),
            );
        } catch (ValidationException $e) {
            // Filament addresses form fields as data.<field>.
            throw ValidationException::withMessages(collect($e->errors())->mapWithKeys(fn (array $m, string $k): array => ["data.$k" => $m])->all());
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Invitation envoyée';
    }
}
