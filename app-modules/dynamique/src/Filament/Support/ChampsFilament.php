<?php

declare(strict_types=1);

namespace Modules\Dynamique\Filament\Support;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Dynamique\Application\Services\ChampService;
use Modules\Dynamique\Domain\Enums\EntiteChamp;
use Modules\Dynamique\Domain\Enums\TypeChamp;
use Modules\Dynamique\Domain\Models\ChampPersonnalise;

/**
 * Turns the school's custom field definitions into Filament form fields, table columns and filters
 * (spec section 3.4). Values live in the JSON column `champs_perso`; the state path `champs_perso.<cle>`
 * is shared by the form, the columns and the validation errors.
 */
final class ChampsFilament
{
    /** @return list<Section> empty when the school defined no field, so the form does not show an empty box */
    public static function sections(EntiteChamp $entite): array
    {
        $champs = app(ChampService::class)->lister(auth()->user(), $entite);
        if ($champs->isEmpty()) {
            return [];
        }

        return [
            Section::make('Champs personnalisés')
                ->description('Informations propres à votre école (menu Configuration → Champs personnalisés).')
                ->columns(2)
                ->schema($champs->map(fn (ChampPersonnalise $c) => self::champ($c))->all()),
        ];
    }

    /** @return list<Column> */
    public static function colonnes(EntiteChamp $entite): array
    {
        return app(ChampService::class)->lister(auth()->user(), $entite)->values()->map(function (ChampPersonnalise $c, int $rang) {
            // A dotted name would be read as a relationship, so the column has its own flat name and reads the JSON itself.
            $nom = "champs_perso_{$c->cle}";
            $colonne = $c->type === TypeChamp::Booleen
                ? IconColumn::make($nom)->boolean()
                : TextColumn::make($nom)->placeholder('—');

            return $colonne->state(fn (Model $record): mixed => $record->champs_perso[$c->cle] ?? null)->label($c->libelle)// Only the first three show by default so the register stays readable; the user toggles the rest on.
                ->toggleable(isToggledHiddenByDefault: $rang >= 3)
                // RG-D5: custom columns are sortable and searchable through the JSON path (->> on PostgreSQL).
                ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy("champs_perso->{$c->cle}", $direction))
                ->searchable(query: fn (Builder $query, string $search): Builder => $query->orWhere("champs_perso->{$c->cle}", 'like', "%{$search}%"), isIndividual: false);
        })->all();
    }

    /** @return list<BaseFilter> list and yes/no fields only: the useful exact filters */
    public static function filtres(EntiteChamp $entite): array
    {
        return app(ChampService::class)->lister(auth()->user(), $entite)
            ->filter(fn (ChampPersonnalise $c): bool => in_array($c->type, [TypeChamp::Liste, TypeChamp::Booleen], true))
            ->map(function (ChampPersonnalise $c) {
                $nom = "champs_perso_{$c->cle}";
                if ($c->type === TypeChamp::Booleen) {
                    return TernaryFilter::make($nom)->label($c->libelle)
                        ->queries(
                            true: fn (Builder $query): Builder => $query->where("champs_perso->{$c->cle}", true),
                            false: fn (Builder $query): Builder => $query->where(fn (Builder $q) => $q->whereNull("champs_perso->{$c->cle}")->orWhere("champs_perso->{$c->cle}", false)),
                            blank: fn (Builder $query): Builder => $query,
                        );
                }

                return SelectFilter::make($nom)->label($c->libelle)->options(array_combine($c->options ?? [], $c->options ?? []))
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, string $valeur) => $q->where("champs_perso->{$c->cle}", $valeur),
                    ));
            })->values()->all();
    }

    private static function champ(ChampPersonnalise $c): mixed
    {
        $chemin = "champs_perso.{$c->cle}";

        $composant = match ($c->type) {
            TypeChamp::Texte => TextInput::make($chemin)->maxLength(255),
            TypeChamp::Nombre => TextInput::make($chemin)->numeric(),
            TypeChamp::Telephone => TextInput::make($chemin)->tel()->maxLength(20),
            TypeChamp::Date => DatePicker::make($chemin),
            TypeChamp::Booleen => Toggle::make($chemin),
            TypeChamp::Liste => Select::make($chemin)->options(array_combine($c->options ?? [], $c->options ?? [])),
        };

        // Required-ness is enforced by the service (RG-D6) so the error lands on the same state path.
        return $composant->label($c->libelle)->required($c->obligatoire && $c->type !== TypeChamp::Booleen);
    }
}
