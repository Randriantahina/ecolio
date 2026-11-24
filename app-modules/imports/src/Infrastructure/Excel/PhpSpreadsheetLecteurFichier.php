<?php

declare(strict_types=1);

namespace Modules\Imports\Infrastructure\Excel;

use Modules\Imports\Application\DTOs\LectureFichier;
use Modules\Imports\Domain\Contracts\LecteurFichier;
use Modules\Imports\Domain\Exceptions\FichierIllisibleException;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

final class PhpSpreadsheetLecteurFichier implements LecteurFichier
{
    public function feuilles(string $chemin): array
    {
        [$classeur] = $this->charger($chemin);

        return $classeur->getSheetNames();
    }

    public function lire(string $chemin, ?string $feuille = null): LectureFichier
    {
        [$classeur, $avertissements] = $this->charger($chemin);

        $sheet = $feuille === null ? $classeur->getSheet(0) : $classeur->getSheetByName($feuille);
        if ($sheet === null) {
            throw new FichierIllisibleException("Feuille introuvable : $feuille");
        }

        foreach ($sheet->getMergeCells() as $plage) {
            $avertissements[] = "Cellules fusionnées en $plage : vérifiez la ligne d'en-tête.";
        }

        return new LectureFichier($this->lignes($sheet), $avertissements);
    }

    /** @return array{0: Spreadsheet, 1: list<string>} */
    private function charger(string $chemin): array
    {
        $avertissements = [];

        try {
            if (strtolower(pathinfo($chemin, PATHINFO_EXTENSION)) === 'csv') {
                return [$this->chargerCsv($chemin, $avertissements), $avertissements];
            }

            $type = match (strtolower(pathinfo($chemin, PATHINFO_EXTENSION))) {
                'xlsx' => 'Xlsx',
                'xls' => 'Xls',
                default => throw new FichierIllisibleException('Format non pris en charge : utilisez .xlsx, .xls ou .csv.'),
            };
            // Pinning the reader to the extension stops PhpSpreadsheet from "detecting" a text file as something else.
            $lecteur = IOFactory::createReader($type);
            if (! $lecteur->canRead($chemin)) {
                throw new FichierIllisibleException("Ce fichier n'est pas un classeur $type valide.");
            }

            return [$lecteur->load($chemin), $avertissements];
        } catch (FichierIllisibleException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new FichierIllisibleException('Fichier illisible : '.$e->getMessage(), previous: $e);
        }
    }

    /** @param  list<string>  $avertissements */
    private function chargerCsv(string $chemin, array &$avertissements): Spreadsheet
    {
        $contenu = (string) file_get_contents($chemin);
        $contenu = preg_replace('/^\xEF\xBB\xBF/', '', $contenu);

        // RG-M4: badly encoded accents are reported, not silently kept as mojibake.
        if (! mb_check_encoding($contenu, 'UTF-8')) {
            $contenu = mb_convert_encoding($contenu, 'UTF-8', 'Windows-1252');
            $avertissements[] = 'Encodage converti depuis Windows-1252.';
        }

        $premiere = strtok($contenu, "\n") ?: '';
        $delimiteur = collect([';', ',', "\t"])->sortByDesc(fn (string $d): int => substr_count($premiere, $d))->first();

        $temporaire = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($temporaire, $contenu);

        try {
            $lecteur = new Csv;
            $lecteur->setDelimiter($delimiteur)->setInputEncoding('UTF-8');

            return $lecteur->load($temporaire);
        } finally {
            @unlink($temporaire);
        }
    }

    /** @return array<int, list<scalar|null>> */
    private function lignes(Worksheet $feuille): array
    {
        $lignes = [];
        $derniereColonne = Coordinate::columnIndexFromString($feuille->getHighestDataColumn());

        for ($ligne = 1; $ligne <= $feuille->getHighestDataRow(); $ligne++) {
            $cellules = [];
            for ($colonne = 1; $colonne <= $derniereColonne; $colonne++) {
                $cellule = $feuille->getCell([$colonne, $ligne]);
                $cellules[] = $this->valeur($cellule);
            }
            while ($cellules !== [] && end($cellules) === null) {
                array_pop($cellules);
            }
            if ($cellules !== []) {
                $lignes[$ligne] = $cellules;
            }
        }

        return $lignes;
    }

    private function valeur(Cell $cellule): int|float|string|null
    {
        $valeur = $cellule->getCalculatedValue();

        if (is_string($valeur)) {
            $valeur = trim($valeur);

            return $valeur === '' ? null : $valeur;
        }
        if (is_numeric($valeur) && Date::isDateTime($cellule)) {
            return Date::excelToDateTimeObject($valeur)->format('Y-m-d');
        }
        if (is_float($valeur) && floor($valeur) === $valeur && abs($valeur) < 1e15) {
            return (int) $valeur;
        }

        return is_bool($valeur) ? (int) $valeur : $valeur;
    }
}
