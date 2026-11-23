<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Audit\Contracts\JournalAuditRepository;
use App\Domain\Audit\Models\JournalAudit;
use App\Domain\Eleves\Contracts\EleveRepository;
use App\Domain\Eleves\Contracts\InscriptionRepository;
use App\Domain\Eleves\Models\Eleve;
use App\Domain\Finance\Contracts\CompteurRepository;
use App\Domain\Identity\Contracts\UtilisateurRepository;
use App\Domain\Identity\Models\Utilisateur;
use App\Domain\Platform\Contracts\EcoleRepository;
use App\Domain\Platform\Models\Ecole;
use App\Domain\Scolarite\Contracts\AnneeScolaireRepository;
use App\Domain\Scolarite\Contracts\StructureRepository;
use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Niveau;
use App\Infrastructure\Persistence\Eloquent\EloquentAnneeScolaireRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentCompteurRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentEcoleRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentEleveRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentInscriptionRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentJournalAuditRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentStructureRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentUtilisateurRepository;
use App\Policies\AnneeScolairePolicy;
use App\Policies\EcolePolicy;
use App\Policies\ElevePolicy;
use App\Policies\JournalAuditPolicy;
use App\Policies\UtilisateurPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/** Single place where interfaces meet their implementations (Dependency Inversion) and policies are declared. */
final class DomainServiceProvider extends ServiceProvider
{
    public array $bindings = [
        UtilisateurRepository::class => EloquentUtilisateurRepository::class,
        EcoleRepository::class => EloquentEcoleRepository::class,
        JournalAuditRepository::class => EloquentJournalAuditRepository::class,
        CompteurRepository::class => EloquentCompteurRepository::class,
        AnneeScolaireRepository::class => EloquentAnneeScolaireRepository::class,
        StructureRepository::class => EloquentStructureRepository::class,
        EleveRepository::class => EloquentEleveRepository::class,
        InscriptionRepository::class => EloquentInscriptionRepository::class,
    ];

    public function boot(): void
    {
        // Models live under Domain/, so Laravel's name-based policy discovery cannot find these.
        Gate::policy(Utilisateur::class, UtilisateurPolicy::class);
        Gate::policy(Ecole::class, EcolePolicy::class);
        Gate::policy(JournalAudit::class, JournalAuditPolicy::class);
        Gate::policy(Eleve::class, ElevePolicy::class);
        // Calendar, levels and classes share one rule: staff read, the directeur writes (spec section 1).
        Gate::policy(AnneeScolaire::class, AnneeScolairePolicy::class);
        Gate::policy(Niveau::class, AnneeScolairePolicy::class);
        Gate::policy(Classe::class, AnneeScolairePolicy::class);

        ResetPassword::createUrlUsing(fn ($user, string $token) => config('app.frontend_url')
            .'/reinitialiser-mot-de-passe?token='.$token.'&email='.urlencode($user->getEmailForPasswordReset()));
    }
}
