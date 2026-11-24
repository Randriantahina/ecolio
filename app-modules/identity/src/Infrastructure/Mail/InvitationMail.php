<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class InvitationMail extends Mailable
{
    public function __construct(
        public readonly string $nomEcole,
        public readonly string $nom,
        public readonly string $identifiant,
        public readonly string $motDePasse,
        public readonly string $urlConnexion,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Votre accès à Écolio – {$this->nomEcole}");
    }

    public function content(): Content
    {
        return new Content(text: 'identity::mail.invitation');
    }
}
