<?php

declare(strict_types=1);

namespace Modules\Eleves\Filament\Resources\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Dynamique\Domain\Contracts\ExporteurTableau;
use Modules\Dynamique\Domain\Enums\EntiteVue;
use Modules\Dynamique\Filament\Concerns\GereVues;
use Modules\Eleves\Application\Services\ExportElevesService;
use Modules\Eleves\Filament\Resources\EleveResource;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ListEleves extends ListRecords
{
    use GereVues;

    protected static string $resource = EleveResource::class;

    protected function entiteVue(): EntiteVue
    {
        return EntiteVue::Eleve;
    }

    protected function getHeaderActions(): array
    {
        return [
            ...$this->vuesActions(),
            Action::make('exporter')->label('Exporter')->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                ->modalDescription('Exporte les élèves de la vue courante (recherche, filtres et tri appliqués).')
                ->schema([Select::make('format')->label('Format')->required()->default('xlsx')->options(['xlsx' => 'Excel (.xlsx)', 'csv' => 'CSV (.csv)'])])
                ->action(function (array $data): StreamedResponse {
                    [$entetes, $lignes] = app(ExportElevesService::class)->tableau(auth()->user(), $this->getFilteredSortedTableQuery()->get());
                    $exporteur = app(ExporteurTableau::class);
                    $contenu = $exporteur->contenu($entetes, $lignes, $data['format']);

                    return response()->streamDownload(fn () => print ($contenu), $exporteur->nomDeFichier('eleves', $data['format']));
                }),
            CreateAction::make()->label('Nouvel élève'),
        ];
    }
}
