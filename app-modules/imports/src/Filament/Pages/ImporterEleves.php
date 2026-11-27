<?php

declare(strict_types=1);

namespace Modules\Imports\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Modules\Dynamique\Application\Services\ChampService;
use Modules\Dynamique\Domain\Enums\EntiteChamp;
use Modules\Imports\Application\Services\ImportService;
use Modules\Imports\Domain\Enums\EntiteImport;
use Modules\Imports\Domain\Enums\ModeImport;
use Modules\Imports\Domain\Exceptions\FichierIllisibleException;
use Modules\Imports\Domain\Models\Import;
use Modules\Imports\Filament\Resources\ImportResource;

/** Steps 1 and 2 of the wizard (spec 4.1): the file, its sheet and header row, then the column mapping. */
final class ImporterEleves extends Page
{
    protected static ?string $title = 'Importer des élèves';

    protected static ?string $navigationLabel = 'Importer des élèves';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowUp;

    protected static string|\UnitEnum|null $navigationGroup = 'Données';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'importer-eleves';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public ?string $importUuid = null;

    /** @var list<string> */
    public array $feuilles = [];

    /** @var list<string> */
    public array $entetes = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('create', Import::class) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(['mode' => ModeImport::Creation->value, 'ligne_entete' => 1]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])->id('form')->livewireSubmitHandler('controler'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Wizard::make([
                Step::make('Fichier')->description('Excel ou CSV')->schema([
                    Radio::make('mode')->label('Que faire des élèves du fichier ?')->required()->default(ModeImport::Creation->value)
                        ->options([
                            ModeImport::Creation->value => 'Créer de nouveaux élèves (un matricule déjà connu est refusé)',
                            ModeImport::MiseAJour->value => 'Mettre à jour les élèves existants (par matricule, sinon par nom + date de naissance après confirmation)',
                        ]),
                    FileUpload::make('fichier')->label('Fichier')->required()->storeFiles(false)->maxSize(20480)
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel',
                            'text/csv', 'text/plain', 'application/csv', 'application/octet-stream',
                        ])
                        ->helperText('.xlsx, .xls ou .csv, 5 000 lignes au plus.'),
                ])->afterValidation(fn () => $this->ouvrirFichier()),
                Step::make('Feuille et en-tête')->schema([
                    Select::make('feuille')->label('Feuille')->required()->options(fn (): array => array_combine($this->feuilles, $this->feuilles) ?: []),
                    TextInput::make('ligne_entete')->label('Numéro de la ligne d\'en-tête')->numeric()->minValue(1)->required()->default(1)
                        ->helperText('La ligne qui contient « Nom », « Prénoms »… Les lignes de titre au-dessus sont ignorées.'),
                ])->afterValidation(fn () => $this->preparerMapping()),
                Step::make('Colonnes')->description('Où va chaque colonne ?')->schema(fn (): array => $this->champsMapping()),
            ])->submitAction(new HtmlString(Blade::render('<x-filament::button type="submit">Contrôler le fichier</x-filament::button>'))),
        ]);
    }

    public function updatedData(mixed $valeur, string $cle): void
    {
        // A new file means a new batch: never map columns of the old one.
        if ($cle === 'fichier') {
            $this->importUuid = null;
        }
    }

    public function ouvrirFichier(): void
    {
        if ($this->importUuid !== null) {
            return;
        }

        $fichier = Arr::first(Arr::wrap($this->form->getState()['fichier'] ?? null));

        try {
            $import = app(ImportService::class)->ouvrir(
                auth()->user(), EntiteImport::Eleves, $fichier->getRealPath(), $fichier->getClientOriginalName(),
                ModeImport::from($this->data['mode']),
            );
            $this->importUuid = $import->uuid;
            $this->feuilles = app(ImportService::class)->feuilles($import);
            $this->data['feuille'] = $this->feuilles[0] ?? null;
        } catch (ValidationException $e) {
            throw $this->surFormulaire($e);
        } catch (FichierIllisibleException $e) {
            throw ValidationException::withMessages(['data.fichier' => $e->getMessage()]);
        }
    }

    public function preparerMapping(): void
    {
        $service = app(ImportService::class);
        $import = $service->retrouver(auth()->user(), (string) $this->importUuid);
        $apercu = $service->apercu($import, $this->data['feuille'], (int) $this->data['ligne_entete']);

        $this->entetes = $apercu['entetes'];
        $proposition = $service->proposerMapping(auth()->user(), $import, $this->entetes);
        $this->data['mapping'] = collect($this->entetes)->mapWithKeys(fn (string $entete, int $i) => ["c$i" => $proposition[$entete]])->all();
    }

    public function controler(): void
    {
        $service = app(ImportService::class);
        $import = $service->retrouver(auth()->user(), (string) $this->importUuid);
        $mapping = collect($this->entetes)->mapWithKeys(fn (string $entete, int $i) => [$entete => $this->data['mapping']["c$i"] ?? 'ignorer'])->all();

        try {
            $import = $service->appliquerMapping(auth()->user(), $import, $this->data['feuille'], (int) $this->data['ligne_entete'], $mapping);
        } catch (ValidationException $e) {
            throw $this->surFormulaire($e);
        }

        $this->redirect(ImportResource::getUrl('controle', ['record' => $import]));
    }

    /** @return list<Component> */
    private function champsMapping(): array
    {
        $options = $this->optionsMapping();
        $champs = [Text::make('Associez chaque colonne du fichier à un champ. Le nom est obligatoire ; « Ignorer » laisse la colonne de côté.')];
        foreach ($this->entetes as $i => $entete) {
            $champs[] = Select::make("mapping.c$i")->label($entete)->options($options)->required()->searchable();
        }

        return $champs;
    }

    /** @return array<string, string> */
    private function optionsMapping(): array
    {
        $options = [
            'ignorer' => 'Ignorer cette colonne', 'matricule' => 'Matricule', 'nom' => 'Nom', 'prenoms' => 'Prénoms', 'sexe' => 'Sexe',
            'date_naissance' => 'Date de naissance', 'lieu_naissance' => 'Lieu de naissance', 'classe' => 'Classe',
        ];
        foreach (app(ChampService::class)->lister(auth()->user(), EntiteChamp::Eleve) as $champ) {
            $options["champ:{$champ->cle}"] = "Champ personnalisé : {$champ->libelle}";
        }

        return $options + [
            'nouveau:texte' => 'Créer un nouveau champ (texte)', 'nouveau:nombre' => 'Créer un nouveau champ (nombre)',
            'nouveau:date' => 'Créer un nouveau champ (date)', 'nouveau:booleen' => 'Créer un nouveau champ (oui/non)',
            'nouveau:telephone' => 'Créer un nouveau champ (téléphone)',
        ];
    }

    private function surFormulaire(ValidationException $e): ValidationException
    {
        $cle = fn (string $k): string => $k === 'mapping' ? 'data.mapping' : "data.$k";

        return ValidationException::withMessages(collect($e->errors())->mapWithKeys(fn (array $m, string $k): array => [$cle($k) => $m])->all());
    }
}
