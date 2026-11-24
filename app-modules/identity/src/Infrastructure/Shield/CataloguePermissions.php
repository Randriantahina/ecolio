<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Shield;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Facades\Filament;

/** Every permission Shield knows for the school panel: resource actions plus the custom keys of the config. */
final class CataloguePermissions
{
    /** @var list<string>|null */
    private static ?array $noms = null;

    /**
     * Computed once per process: discovery walks every resource and page, far too slow to repeat for each test
     * or each seeding. The catalogue only changes with the code, i.e. with a new process.
     *
     * @return list<string>
     */
    public static function noms(): array
    {
        return self::$noms ??= self::decouvrir();
    }

    /** @return list<string> */
    private static function decouvrir(): array
    {
        // Shield discovers entities of the *current* panel; seeders and queue workers have none selected.
        Filament::setCurrentPanel(Filament::getPanel('ecole'));

        return array_values(array_unique(FilamentShield::getEntitiesPermissions()));
    }
}
