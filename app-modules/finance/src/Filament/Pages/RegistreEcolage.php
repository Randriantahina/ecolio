<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\Pages;

use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Application\DTOs\EncaisserDto;
use Modules\Finance\Application\Services\PaiementService;
use Modules\Finance\Application\Services\RegistreEcolageService;
use Modules\Finance\Domain\Contracts\RecuPdfGenerator;
use Modules\Finance\Domain\Enums\ModePaiement;
use Modules\Finance\Domain\Enums\StatutEcheance;
use Modules\Finance\Domain\Models\Paiement;
use Modules\Finance\Filament\Support\Ariary;
use Modules\Finance\Filament\Support\ModePaiementLabels;
use Modules\Finance\Http\Resources\PaiementResource;
use Modules\Scolarite\Application\Services\StructureService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The tuition register (spec M4): one row per instalment for a class and a month. Paying creates a payment
 * and a numbered receipt through PaiementService; a paid row can only be voided, never edited (RG-R2).
 */
final class RegistreEcolage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Registre d\'écolage';

    protected static ?string $navigationLabel = 'Registre d\'écolage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.finance.registre-ecolage';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Paiement::class) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?string $search, ?array $filters): array => $this->lignes($search, $filters))
            ->columns([
                TextColumn::make('matricule')->label('Matricule')->fontFamily('mono'),
                TextColumn::make('nom')->label('Élève')->weight('bold'),
                TextColumn::make('libelle')->label('Frais'),
                TextColumn::make('montant_ar')->label('Montant')->alignEnd()
                    ->formatStateUsing(fn (int $state): string => Ariary::format($state)),
                TextColumn::make('paye_ar')->label('Payé')->alignEnd()
                    ->formatStateUsing(fn (int $state): string => Ariary::format($state)),
                TextColumn::make('solde_ar')->label('Reste')->alignEnd()
                    ->formatStateUsing(fn (int $state): string => Ariary::format($state)),
                TextColumn::make('statut')->label('Statut')->badge()
                    ->formatStateUsing(fn (string $state): string => self::statuts()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        StatutEcheance::Paye->value => 'success',
                        StatutEcheance::Partiel->value => 'warning',
                        StatutEcheance::Annule->value => 'gray',
                        default => 'danger',
                    }),
                TextColumn::make('recu')->label('Reçu')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('classe')->label('Classe')->options(fn (): array => $this->classes())
                    ->default(fn (): ?string => array_key_first($this->classes()))->selectablePlaceholder(false),
                SelectFilter::make('periode')->label('Mois')->options(fn (): array => $this->periodes())
                    ->default(fn (): ?string => $this->periodeParDefaut())->selectablePlaceholder(false),
            ], layout: FiltersLayout::AboveContent)
            ->recordActions([
                $this->encaisserAction(),
                $this->recuAction(),
                $this->annulerAction(),
            ])
            ->paginated(false)
            ->emptyStateHeading('Aucune échéance')
            ->emptyStateDescription('Choisissez une classe et un mois, ou créez les tarifs de l\'année.');
    }

    private function encaisserAction(): Action
    {
        return Action::make('encaisser')
            ->label('Encaisser')
            ->icon(Heroicon::OutlinedBanknotes)
            ->visible(fn (array $record): bool => $record['solde_ar'] > 0 && $record['statut'] !== StatutEcheance::Annule->value)
            ->modalHeading(fn (array $record): string => "Encaisser · {$record['nom']} · {$record['libelle']}")
            ->fillForm(fn (array $record): array => [
                'montant_ar' => $record['solde_ar'],
                'mode' => ModePaiement::Especes->value,
                'date_paiement' => now()->toDateString(),
            ])
            ->schema([
                TextInput::make('montant_ar')->label('Montant')->suffix('Ar')->numeric()->integer()->minValue(1)->required()
                    ->helperText('Un montant inférieur au reste enregistre un paiement partiel.'),
                Select::make('mode')->label('Mode de paiement')->options(ModePaiementLabels::options())->required()->live(),
                TextInput::make('reference_externe')->label('Référence de la transaction')->maxLength(100)
                    ->required(fn ($get): bool => $get('mode') !== ModePaiement::Especes->value),
                DatePicker::make('date_paiement')->label('Date du paiement')->required()->maxDate(now()),
            ])
            ->action(function (array $data, array $record, Action $action): void {
                try {
                    $result = app(PaiementService::class)->encaisser(auth()->user(), new EncaisserDto(
                        eleveUuid: $record['eleve_uuid'],
                        lignes: [['echeance_uuid' => $record['key'], 'montant_ar' => (int) $data['montant_ar']]],
                        mode: $data['mode'],
                        datePaiement: (string) $data['date_paiement'],
                        referenceExterne: $data['reference_externe'] ?? null,
                        clientUuid: null,
                    ));
                } catch (ValidationException $e) {
                    Notification::make()->danger()->title('Paiement refusé')->body((string) collect($e->errors())->flatten()->first())->send();
                    $action->halt();

                    return;
                }

                Notification::make()->success()->title('Reçu n° '.$result['paiement']->recu->numero_affiche.' émis')->send();
            });
    }

    private function recuAction(): Action
    {
        return Action::make('recu')
            ->label('Reçu PDF')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->visible(fn (array $record): bool => $record['paiement_uuid'] !== null)
            ->action(function (array $record): StreamedResponse {
                $paiements = app(PaiementService::class);
                $paiement = $paiements->voir(auth()->user(), $record['paiement_uuid']);
                $recu = (new PaiementResource($paiement, $paiements->resteAPayer($paiement->eleve_id)))->resolve();
                $contenu = app(RecuPdfGenerator::class)->generate($recu);

                return response()->streamDownload(fn () => print ($contenu), 'recu-'.$recu['numero'].'.pdf', ['Content-Type' => 'application/pdf']);
            });
    }

    private function annulerAction(): Action
    {
        return Action::make('annuler')
            ->label('Annuler le paiement')
            ->color('danger')
            ->icon(Heroicon::OutlinedXCircle)
            ->visible(fn (array $record): bool => $record['paiement_uuid'] !== null && (auth()->user()?->can('annuler', Paiement::class) ?? false))
            ->modalDescription('Le reçu garde son numéro et reste consultable, marqué annulé.')
            ->schema([Textarea::make('motif')->label('Motif de l\'annulation')->required()->maxLength(255)])
            ->action(function (array $data, array $record, Action $action): void {
                try {
                    app(PaiementService::class)->annuler(auth()->user(), $record['paiement_uuid'], $data['motif']);
                } catch (ValidationException $e) {
                    Notification::make()->danger()->title('Annulation refusée')->body((string) collect($e->errors())->flatten()->first())->send();
                    $action->halt();

                    return;
                }

                Notification::make()->success()->title('Paiement annulé')->send();
            });
    }

    /**
     * @param  array<string, mixed>|null  $filters
     * @return array<string, array<string, mixed>> Keyed by echeance uuid, which is the table record key.
     */
    private function lignes(?string $search, ?array $filters): array
    {
        $classe = $filters['classe']['value'] ?? array_key_first($this->classes());
        if ($classe === null) {
            return [];
        }

        $periode = $filters['periode']['value'] ?? $this->periodeParDefaut($classe);
        $needle = $search !== null ? mb_strtolower(trim($search)) : '';
        $lignes = [];

        foreach (app(RegistreEcolageService::class)->pour(auth()->user(), $classe, $periode) as $groupe) {
            $eleve = $groupe['eleve'];
            if ($needle !== '' && ! str_contains(mb_strtolower($eleve['matricule'].' '.$eleve['nom'].' '.($eleve['prenoms'] ?? '')), $needle)) {
                continue;
            }

            foreach ($groupe['echeances'] as $e) {
                $lignes[$e['uuid']] = [
                    'key' => $e['uuid'],
                    'eleve_uuid' => $eleve['uuid'],
                    'matricule' => $eleve['matricule'],
                    'nom' => trim($eleve['nom'].' '.($eleve['prenoms'] ?? '')),
                    'libelle' => $e['libelle'],
                    'montant_ar' => $e['montant_ar'] - $e['remise_ar'],
                    'paye_ar' => $e['paye_ar'],
                    'solde_ar' => $e['solde_ar'],
                    'statut' => $e['statut'],
                    'recu' => $e['recu'],
                    'paiement_uuid' => $e['paiement_uuid'],
                ];
            }
        }

        return $lignes;
    }

    /** @return array<string, string> */
    private function classes(): array
    {
        return app(StructureService::class)->classes(auth()->user(), null)->sortBy('nom')->pluck('nom', 'uuid')->all();
    }

    /** @return array<string, string> Month code => label, taken from the instalments that exist. */
    private function periodes(?string $classe = null): array
    {
        $classe ??= $this->tableFilters['classe']['value'] ?? array_key_first($this->classes());
        if ($classe === null) {
            return [];
        }

        $codes = collect(app(RegistreEcolageService::class)->pour(auth()->user(), $classe, null))
            ->flatMap(fn (array $groupe) => collect($groupe['echeances']))
            ->pluck('periode')->filter()->unique()->sort()->values();

        return $codes->mapWithKeys(fn (string $code): array => [
            $code => mb_convert_case(CarbonImmutable::createFromFormat('Y-m', $code)->locale('fr')->translatedFormat('F Y'), MB_CASE_TITLE),
        ])->all();
    }

    private function periodeParDefaut(?string $classe = null): ?string
    {
        $periodes = array_keys($this->periodes($classe));

        return in_array(now()->format('Y-m'), $periodes, true) ? now()->format('Y-m') : ($periodes[0] ?? null);
    }

    /** @return array<string, string> */
    private static function statuts(): array
    {
        return [
            StatutEcheance::Du->value => 'À payer',
            StatutEcheance::Partiel->value => 'Partiel',
            StatutEcheance::Paye->value => 'Payé',
            StatutEcheance::Annule->value => 'Annulé',
        ];
    }
}
