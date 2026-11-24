<?php

declare(strict_types=1);

namespace Modules\Platform\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;
use Modules\Identity\Domain\Enums\Role;

/** School settings in one place (as in mintizy-v2): school structure, custom fields, users and roles. */
final class ParametresCluster extends Cluster
{
    protected static ?string $slug = 'parametres';

    protected static ?string $navigationLabel = 'Paramètres';

    protected static ?string $clusterBreadcrumb = 'Paramètres';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    protected static ?int $navigationSort = 100;

    /** Settings are the administration's: an enseignant only ever sees the absences module. */
    public static function canAccess(): bool
    {
        return auth()->user()?->aRole(Role::Administration) ?? false;
    }
}
