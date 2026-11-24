<?php

declare(strict_types=1);

namespace Modules\Dynamique\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Modules\Dynamique\Application\Services\VueService;
use Modules\Dynamique\Domain\Enums\EntiteVue;
use Modules\Dynamique\Domain\Models\VueTableau;
use Modules\Identity\Domain\Enums\Role;

/**
 * Saved views for a Filament list page (US3.4): captures filters, search, sort and column choices,
 * and restores them. The page declares which entity it shows.
 */
trait GereVues
{
    abstract protected function entiteVue(): EntiteVue;

    /** Livewire calls mount<TraitName> once: the user's default view opens with the page. */
    public function mountGereVues(): void
    {
        $defaut = app(VueService::class)->parDefaut(auth()->user(), $this->entiteVue());
        if ($defaut !== null) {
            $this->appliquerVue($defaut, ouverture: true);
        }
    }

    /** @return list<ActionGroup> */
    protected function vuesActions(): array
    {
        return [ActionGroup::make([$this->enregistrerVueAction(), $this->chargerVueAction(), $this->supprimerVueAction()])
            ->label('Vues')->icon(Heroicon::OutlinedBookmark)->button()->color('gray')];
    }

    private function enregistrerVueAction(): Action
    {
        return Action::make('enregistrerVue')->label('Enregistrer cette vue')->icon(Heroicon::OutlinedBookmarkSquare)
            ->modalDescription('Mémorise la recherche, les filtres, le tri et les colonnes affichées.')
            ->schema([
                TextInput::make('nom')->label('Nom de la vue')->required()->maxLength(80)->placeholder('6e A, impayés septembre'),
                Toggle::make('est_partagee')->label('Partager avec toute l\'école')->visible(fn (): bool => auth()->user()->aRole(Role::Administration)),
                Toggle::make('est_par_defaut')->label('Ouvrir cette vue par défaut'),
            ])
            ->action(function (array $data): void {
                app(VueService::class)->enregistrer(auth()->user(), $this->entiteVue(), $data['nom'], [
                    'filtres' => $this->tableFilters ?? [],
                    'recherche' => $this->tableSearch,
                    'tri' => $this->tableSort,
                    'colonnes' => $this->tableColumns ?? [],
                ], (bool) ($data['est_partagee'] ?? false), (bool) ($data['est_par_defaut'] ?? false));
                Notification::make()->success()->title('Vue enregistrée')->send();
            });
    }

    private function chargerVueAction(): Action
    {
        return Action::make('chargerVue')->label('Appliquer une vue')->icon(Heroicon::OutlinedFunnel)
            ->schema([Select::make('vue')->label('Vue')->required()->options(fn (): array => $this->optionsVues())])
            ->action(function (array $data): void {
                $this->appliquerVue(app(VueService::class)->charger(auth()->user(), $data['vue']));
            });
    }

    private function supprimerVueAction(): Action
    {
        return Action::make('supprimerVue')->label('Supprimer une vue')->icon(Heroicon::OutlinedTrash)->color('danger')
            ->schema([Select::make('vue')->label('Vue')->required()->options(fn (): array => $this->optionsVues())])
            ->action(function (array $data): void {
                app(VueService::class)->supprimer(auth()->user(), $data['vue']);
                Notification::make()->success()->title('Vue supprimée')->send();
            });
    }

    /** @return array<string, string> */
    private function optionsVues(): array
    {
        return app(VueService::class)->lister(auth()->user(), $this->entiteVue())
            ->mapWithKeys(fn (VueTableau $v): array => [$v->uuid => $v->nom.($v->est_partagee ? ' (partagée)' : '')])->all();
    }

    private function appliquerVue(VueTableau $vue, bool $ouverture = false): void
    {
        $etat = $vue->etat;
        $this->tableFilters = $etat['filtres'] ?? [];
        $this->tableDeferredFilters = $this->tableFilters;
        $this->tableSearch = $etat['recherche'] ?? null;
        $this->tableSort = $etat['tri'] ?? null;
        // While the page is mounting the table is not built yet: it will read the properties set above.
        if ($ouverture) {
            return;
        }
        // While the page is mounting the table is not built yet: it will read the properties set above.
        if ($ouverture) {
            return;
        }
        if (filled($etat['colonnes'] ?? [])) {
            $this->applyTableColumnManager($etat['colonnes']);
        }
        $this->resetPage();
    }
}
