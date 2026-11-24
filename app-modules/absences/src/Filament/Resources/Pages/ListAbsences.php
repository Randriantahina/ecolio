<?php

declare(strict_types=1);

namespace Modules\Absences\Filament\Resources\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Absences\Application\Services\ExportAbsencesService;
use Modules\Absences\Filament\Pages\AppelDuJour;
use Modules\Absences\Filament\Resources\AbsenceResource;
use Modules\Dynamique\Domain\Contracts\ExporteurTableau;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ListAbsences extends ListRecords
{
    protected static string $resource = AbsenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exporter')->label('Exporter')->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                ->schema([Select::make('format')->label('Format')->required()->default('xlsx')->options(['xlsx' => 'Excel (.xlsx)', 'csv' => 'CSV (.csv)'])])
                ->action(function (array $data): StreamedResponse {
                    [$entetes, $lignes] = $this->tableauExport();
                    $exporteur = app(ExporteurTableau::class);
                    $contenu = $exporteur->contenu($entetes, $lignes, $data['format']);

                    return response()->streamDownload(fn () => print ($contenu), $exporteur->nomDeFichier('absences', $data['format']));
                }),
            Action::make('appel')->label('Faire l\'appel')->url(fn (): string => AppelDuJour::getUrl())];
    }

    /** @return array{0: list<string>, 1: list<list<scalar|null>>} */
    private function tableauExport(): array
    {
        $tableau = app(ExportAbsencesService::class)->tableau($this->getFilteredSortedTableQuery()->get());

        return [$tableau[0], array_slice($tableau, 1)];
    }
}
