<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Sms;

use Illuminate\Support\Facades\Log;
use Modules\Identity\Domain\Contracts\EnvoyeurSms;

/**
 * TODO(phase-5): replace with the real SMS gateway adapter (provider not chosen yet, spec [À confirmer]).
 * Until then nothing leaves the server: only the fact is logged, never the text, which holds a password.
 */
final class JournalEnvoyeurSms implements EnvoyeurSms
{
    public function envoyer(string $numero, string $message): void
    {
        Log::warning('SMS not sent: no gateway configured.', ['numero' => $numero, 'longueur' => mb_strlen($message)]);
    }
}
