<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Contracts;

/** Port for sending a text message; the real gateway is an adapter chosen with the communications phase. */
interface EnvoyeurSms
{
    public function envoyer(string $numero, string $message): void;
}
