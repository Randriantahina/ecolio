<?php

declare(strict_types=1);

namespace Modules\Imports\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Imports\Domain\Contracts\LecteurFichier;
use Modules\Imports\Infrastructure\Excel\PhpSpreadsheetLecteurFichier;

final class ImportsServiceProvider extends ServiceProvider
{
    /** Dependency Inversion: the module's interfaces meet their implementations here. */
    public array $bindings = [
        LecteurFichier::class => PhpSpreadsheetLecteurFichier::class,
    ];

    public function register(): void {}

    public function boot(): void {}
}
