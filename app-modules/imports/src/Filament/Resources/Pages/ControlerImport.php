<?php

declare(strict_types=1);

namespace Modules\Imports\Filament\Resources\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use Modules\Imports\Application\Services\ImportService;
use Modules\Imports\Domain\Enums\StatutImport;
use Modules\Imports\Domain\Enums\StatutLigneImport;
use Modules\Imports\Domain\Models\ImportLigne;
use Modules\Imports\Filament\Resources\ImportResource;
use Modules\Scolarite\Application\Services\StructureService;

/** Steps 3 to 5 (spec 4.1): staged rows with their errors, fixes, the run and the report. */
final class ControlerImport extends Page implements HasTable
{
    use InteractsWithRecord, InteractsWithTable;

    protected static string $resource = ImportResource::class;

    protected static ?string $title = 'Contrôle de l\'import';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Résumé')->schema([Text::make(fn (): string => $this->resume())]),
            Section::make('Rapport')->visible(fn (): bool => ! empty($this->record->rapport))
                ->schema([Text::make(fn (): string => collect($this->record->rapport)->map(fn (array $r) => 'Ligne '.$r['numero'].' : '.implode(' ', $r['messages']))->implode("\n"))]),
            EmbeddedTable::make(),
        ]);
    }

    private function resume(): string
    {
        $i = $this->record;

        return ImportResource::statuts()[$i->statut->value]." · {$i->nom_original} · {$i->total} ligne(s) : "
            ."{$i->crees} créé(s), {$i->mis_a_jour} mis à jour, {$i->ignores} ignoré(s), {$i->en_erreur} en erreur.";
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => ImportLigne::query()->where('import_id', $this->record->id))
            ->defaultSort('numero')
            ->poll(fn (): ?string => $this->record->statut === StatutImport::EnCours ? '3s' : null)
            ->columns([
                TextColumn::make('numero')->label('Ligne')->sortable(),
                TextColumn::make('matricule')->label('Matricule')->state(fn (ImportLigne $l) => $l->donnees['matricule'] ?? null)->placeholder('—'),
                TextColumn::make('nom')->label('Nom')->state(fn (ImportLigne $l) => trim(($l->donnees['nom'] ?? '').' '.($l->donnees['prenoms'] ?? '')))->weight('bold'),
                TextColumn::make('classe')->label('Classe')->state(fn (ImportLigne $l) => $l->donnees['classe'] ?? null)->placeholder('—'),
                TextColumn::make('problemes')->label('Problèmes')->state(fn (ImportLigne $l): string => collect($l->erreurs)->pluck('message')->implode(' '))->wrap()->color('danger'),
                TextColumn::make('statut')->label('État')->badge()
                    ->formatStateUsing(fn (StatutLigneImport $state): string => ['ok' => 'Prête', 'erreur' => 'À corriger', 'exclue' => 'Exclue', 'importee' => 'Importée'][$state->value])
                    ->color(fn (StatutLigneImport $state): string => ['ok' => 'success', 'erreur' => 'danger', 'exclue' => 'gray', 'importee' => 'info'][$state->value]),
            ])
            ->filters([
                SelectFilter::make('statut')->label('État')->options(['ok' => 'Prêtes', 'erreur' => 'À corriger', 'exclue' => 'Exclues', 'importee' => 'Importées']),
            ])
            ->recordActions([
                Action::make('corriger')->label('Corriger')->icon(Heroicon::OutlinedPencilSquare)
                    ->visible(fn (ImportLigne $l): bool => $this->modifiable() && $l->statut !== StatutLigneImport::Exclue)
                    ->fillForm(fn (ImportLigne $l): array => $l->donnees)
                    ->schema(fn (): array => collect($this->record->mapping ?? [])->filter(fn ($c) => $c !== 'ignorer')
                        ->map(fn (string $cible) => TextInput::make($cible)->label($this->libelleCible($cible)))->values()->all())
                    ->action(fn (ImportLigne $l, array $data) => $this->appliquer(fn (ImportService $s) => $s->corriger(auth()->user(), $this->record, $l->numero, $data))),
                Action::make('confirmer')->label('Confirmer')->icon(Heroicon::OutlinedCheckCircle)->color('success')
                    ->visible(fn (ImportLigne $l): bool => $this->modifiable() && collect($l->erreurs)->contains(fn (array $e) => in_array($e['code'], ['doublon', 'a_confirmer'], true)) && ! $l->confirmee)
                    ->action(fn (ImportLigne $l) => $this->appliquer(fn (ImportService $s) => $s->confirmer(auth()->user(), $this->record, $l->numero))),
                Action::make('exclure')->label('Exclure')->icon(Heroicon::OutlinedXMark)->color('gray')
                    ->visible(fn (ImportLigne $l): bool => $this->modifiable() && $l->statut !== StatutLigneImport::Exclue)
                    ->action(fn (ImportLigne $l) => $this->appliquer(fn (ImportService $s) => $s->exclure(auth()->user(), $this->record, $l->numero))),
                Action::make('reinclure')->label('Réintégrer')->icon(Heroicon::OutlinedArrowUturnLeft)->color('gray')
                    ->visible(fn (ImportLigne $l): bool => $this->modifiable() && $l->statut === StatutLigneImport::Exclue)
                    ->action(fn (ImportLigne $l) => $this->appliquer(fn (ImportService $s) => $s->exclure(auth()->user(), $this->record, $l->numero, false))),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('creerClasse')->label('Créer une classe manquante')->icon(Heroicon::OutlinedPlusCircle)->color('gray')
                ->visible(fn (): bool => $this->modifiable())
                ->schema([
                    TextInput::make('nom')->label('Nom de la classe')->required()->default(fn (): ?string => $this->premiereClasseInconnue()),
                    Select::make('niveau_uuid')->label('Niveau')->required()
                        ->options(fn (): array => app(StructureService::class)->niveaux(auth()->user())->pluck('nom', 'uuid')->all()),
                ])
                ->action(fn (array $data) => $this->appliquer(fn (ImportService $s) => $s->creerClasseManquante(auth()->user(), $this->record, $data['nom'], $data['niveau_uuid']))),
            Action::make('lancer')->label('Lancer l\'import')->icon(Heroicon::OutlinedPlay)->color('success')
                ->visible(fn (): bool => $this->modifiable())
                ->requiresConfirmation()
                ->modalDescription(fn (): string => "{$this->record->total} ligne(s) dans le fichier, dont {$this->record->en_erreur} encore en erreur (ignorées) et {$this->record->ignores} exclue(s). Rien n'est écrit tant que vous ne confirmez pas.")
                ->action(function (): void {
                    $this->appliquer(fn (ImportService $s) => $s->lancer(auth()->user(), $this->record));
                    Notification::make()->success()->title('Import lancé')->body('Il s\'exécute en arrière-plan ; la page se met à jour toute seule.')->send();
                }),
            Action::make('annuler')->label('Annuler l\'import')->icon(Heroicon::OutlinedArrowUturnLeft)->color('danger')
                ->visible(fn (): bool => $this->record->peutEtreAnnule())
                ->requiresConfirmation()
                ->modalDescription('Les élèves créés par cet import sont supprimés et les fiches mises à jour retrouvent leurs valeurs d\'avant. Possible pendant 7 jours.')
                ->action(function (): void {
                    $this->appliquer(fn (ImportService $s) => $s->annuler(auth()->user(), $this->record));
                    Notification::make()->success()->title('Import annulé')->send();
                }),
        ];
    }

    private function modifiable(): bool
    {
        return $this->record->statut === StatutImport::Valide;
    }

    private function premiereClasseInconnue(): ?string
    {
        $ligne = ImportLigne::where('import_id', $this->record->id)->get()
            ->first(fn (ImportLigne $l) => collect($l->erreurs)->contains(fn (array $e) => $e['code'] === 'classe_inconnue'));

        return $ligne?->donnees['classe'] ?? null;
    }

    private function libelleCible(string $cible): string
    {
        return str_starts_with($cible, 'champ:') ? 'Champ : '.substr($cible, 6) : ucfirst(str_replace('_', ' ', $cible));
    }

    private function appliquer(callable $action): void
    {
        try {
            $action(app(ImportService::class));
        } catch (ValidationException $e) {
            Notification::make()->danger()->title('Action impossible')->body(collect($e->errors())->flatten()->implode(' '))->send();
        }
        $this->record->refresh();
        $this->resetTable();
    }
}
