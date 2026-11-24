<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Enums;

/** How a new team member receives their first login details: chosen by the administration per invitation. */
enum CanalInvitation: string
{
    case Email = 'email';
    case Sms = 'sms';

    public function libelle(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Sms => 'SMS (numéro de téléphone)',
        };
    }
}
