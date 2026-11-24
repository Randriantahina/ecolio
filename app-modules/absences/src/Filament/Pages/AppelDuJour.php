<?php

declare(strict_types=1);

namespace Modules\Absences\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;
use Modules\Absences\Application\Services\AbsenceService;
use Modules\Absences\Domain\Models\Absence;
use Modules\Eleves\Application\Services\EleveService;
use Modules\Scolarite\Application\Services\StructureService;

/**
 * "Everyone present, then untick the absentees" (spec 0.1): the form lists the class, the user marks
 * who is absent and who is justified; saving replaces the stored set for that class, day and period.
 *
 * @property-read Schema $form
 */
final class AppelDuJour extends Page
{
    protected static ?string $title = 'Appel du jour';

    protected static ?string $navigationLabel = 'Appel du jour';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Vie scolaire';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Absence::class) ?? false;
    }

    public function mount(): void
    {
        $classes = $this->classes();

        $this->form->fill([
            'classe_uuid' => array_key_first($classes),
            'date' => now()->toDateString(),
            'periode' => 'journee',
        ]);
        $this->charger();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $recharger = function (Set $set): void {
            $set('absents', []);
            $set('justifiees', []);
            $this->charger();
        };

        return $schema->components([
            Select::make('classe_uuid')->label('Classe')->options(fn (): array => $this->classes())->required()->live()
                ->afterStateUpdated($recharger)->selectablePlaceholder(false),
            DatePicker::make('date')->label('Date')->required()->maxDate(now())->live()->afterStateUpdated($recharger),
            Select::make('periode')->label('Période')->required()->live()->afterStateUpdated($recharger)->selectablePlaceholder(false)
                ->options(['journee' => 'Journée', 'matin' => 'Matin', 'apres_midi' => 'Après-midi']),
            CheckboxList::make('absents')->label('Élèves absents')
                ->helperText('Tout le monde est présent par défaut : cochez seulement les absents.')
                ->options(fn (Get $get): array => $this->eleves($get('classe_uuid')))->columns(2)->searchable()->live(),
            CheckboxList::make('justifiees')->label('Absences justifiées')
                ->options(fn (Get $get): array => array_intersect_key($this->eleves($get('classe_uuid')), array_flip($get('absents') ?? [])))
                ->columns(2)->visible(fn (Get $get): bool => filled($get('absents'))),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('enregistrer')
                ->footer([
                    Actions::make([
                        Action::make('enregistrer')->label('Enregistrer l\'appel')->submit('enregistrer'),
                    ]),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toutPresents')->label('Tous présents')->color('gray')->action(function (): void {
                $this->data['absents'] = [];
                $this->data['justifiees'] = [];
            }),
        ];
    }

    public function enregistrer(): void
    {
        $data = $this->form->getState();
        $justifiees = $data['justifiees'] ?? [];

        try {
            app(AbsenceService::class)->saisir(
                auth()->user(), $data['classe_uuid'], (string) $data['date'], $data['periode'],
                collect($data['absents'] ?? [])->map(fn (string $uuid): array => [
                    'eleve_uuid' => $uuid,
                    'justifiee' => in_array($uuid, $justifiees, true),
                ])->values()->all(),
            );
        } catch (ValidationException $e) {
            Notification::make()->danger()->title('Appel refusé')->body((string) collect($e->errors())->flatten()->first())->send();

            return;
        }

        Notification::make()->success()->title('Appel enregistré')
            ->body(count($data['absents'] ?? []).' absent(s)')->send();
    }

    /** Re-reads the stored roll-call so reopening a class shows what was already recorded. */
    private function charger(): void
    {
        $classe = $this->data['classe_uuid'] ?? null;
        if ($classe === null || blank($this->data['date'] ?? null)) {
            return;
        }

        $existantes = app(AbsenceService::class)->du(auth()->user(), $classe, (string) $this->data['date'], $this->data['periode'] ?? 'journee');
        $this->data['absents'] = $existantes->map(fn (Absence $a): string => $a->eleve->uuid)->values()->all();
        $this->data['justifiees'] = $existantes->filter(fn (Absence $a): bool => $a->justifiee)->map(fn (Absence $a): string => $a->eleve->uuid)->values()->all();
    }

    /** @return array<string, string> */
    private function classes(): array
    {
        return app(StructureService::class)->classes(auth()->user(), null)->sortBy('nom')->pluck('nom', 'uuid')->all();
    }

    /** @return array<string, string> Active pupils of the class, uuid => "Nom Prénoms". */
    private function eleves(?string $classe): array
    {
        if ($classe === null) {
            return [];
        }

        return collect(app(EleveService::class)->lister(auth()->user(), ['classe' => $classe, 'statut' => 'actif'], 200)->items())
            ->mapWithKeys(fn ($e): array => [$e->uuid => trim($e->nom.' '.($e->prenoms ?? ''))])->all();
    }
}
