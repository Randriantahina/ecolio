<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Identity\Contracts\UtilisateurRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentUtilisateurRepository;
use Illuminate\Support\ServiceProvider;

/** Single place where interfaces meet their implementations (Dependency Inversion). */
final class DomainServiceProvider extends ServiceProvider
{
    public array $bindings = [
        UtilisateurRepository::class => EloquentUtilisateurRepository::class,
    ];
}
