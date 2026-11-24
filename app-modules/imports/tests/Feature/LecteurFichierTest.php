<?php

declare(strict_types=1);

use Modules\Imports\Domain\Contracts\LecteurFichier;
use Modules\Imports\Domain\Exceptions\FichierIllisibleException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function classeur(callable $remplir): string
{
    $classeur = new Spreadsheet;
    $remplir($classeur);
    $chemin = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
    (new Xlsx($classeur))->save($chemin);

    return $chemin;
}

beforeEach(fn () => $this->lecteur = app(LecteurFichier::class));

it('lists the sheets of a workbook', function () {
    $chemin = classeur(function (Spreadsheet $c) {
        $c->getActiveSheet()->setTitle('6e A');
        $c->createSheet()->setTitle('6e B');
    });

    expect($this->lecteur->feuilles($chemin))->toBe(['6e A', '6e B']);
});

it('reads rows keyed by their real row number, empty trailing rows dropped', function () {
    $chemin = classeur(function (Spreadsheet $c) {
        $f = $c->getActiveSheet();
        $f->fromArray([['Matricule', 'Nom'], ['M-001', 'Rakoto'], [null, null], ['M-003', 'Rabe']], null, 'A1');
    });

    $lecture = $this->lecteur->lire($chemin);

    expect($lecture->lignes[1])->toBe(['Matricule', 'Nom'])->and($lecture->lignes[2])->toBe(['M-001', 'Rakoto'])
        ->and($lecture->lignes)->toHaveKeys([1, 2, 4])->not->toHaveKey(3);
});

it('turns date-formatted cells into ISO dates and leaves text dates as typed (RG-M4)', function () {
    $chemin = classeur(function (Spreadsheet $c) {
        $f = $c->getActiveSheet();
        $f->setCellValue('A1', 'Naissance');
        $f->setCellValue('A2', Date::PHPToExcel(new DateTime('2014-03-02')));
        $f->getStyle('A2')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        $f->setCellValueExplicit('A3', '02/03/2014', DataType::TYPE_STRING);
    });

    $lecture = $this->lecteur->lire($chemin);

    expect($lecture->lignes[2][0])->toBe('2014-03-02')->and($lecture->lignes[3][0])->toBe('02/03/2014');
});

it('warns about merged cells instead of guessing (RG-M4)', function () {
    $chemin = classeur(function (Spreadsheet $c) {
        $f = $c->getActiveSheet();
        $f->setCellValue('A1', 'LISTE DES ÉLÈVES 2026');
        $f->mergeCells('A1:D1');
        $f->fromArray([['Matricule', 'Nom']], null, 'A2');
    });

    $lecture = $this->lecteur->lire($chemin);

    expect($lecture->avertissements)->toContain('Cellules fusionnées en A1:D1 : vérifiez la ligne d\'en-tête.');
});

it('keeps numbers as numbers and trims text cells', function () {
    $chemin = classeur(function (Spreadsheet $c) {
        $c->getActiveSheet()->fromArray([['  Rakoto  ', 12.5, 3]], null, 'A1');
    });

    expect($this->lecteur->lire($chemin)->lignes[1])->toBe(['Rakoto', 12.5, 3]);
});

it('reads a semicolon csv written by a French Excel in Windows-1252 and warns about the conversion', function () {
    $chemin = tempnam(sys_get_temp_dir(), 'imp').'.csv';
    file_put_contents($chemin, mb_convert_encoding("Matricule;Nom\nM-001;Éric Rakotô\n", 'Windows-1252', 'UTF-8'));

    $lecture = $this->lecteur->lire($chemin);

    expect($lecture->lignes[2])->toBe(['M-001', 'Éric Rakotô'])->and($lecture->avertissements)->toContain('Encodage converti depuis Windows-1252.');
});

it('reads a comma csv in UTF-8 with BOM', function () {
    $chemin = tempnam(sys_get_temp_dir(), 'imp').'.csv';
    file_put_contents($chemin, "\xEF\xBB\xBFMatricule,Nom\nM-001,Rabe\n");

    expect($this->lecteur->lire($chemin)->lignes[1])->toBe(['Matricule', 'Nom']);
});

it('refuses a file that is not a spreadsheet', function () {
    $chemin = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
    file_put_contents($chemin, 'pas un classeur');

    expect(fn () => $this->lecteur->feuilles($chemin))->toThrow(FichierIllisibleException::class);
});

it('refuses a missing sheet name', function () {
    $chemin = classeur(fn (Spreadsheet $c) => $c->getActiveSheet()->setTitle('A'));

    expect(fn () => $this->lecteur->lire($chemin, 'Inconnue'))->toThrow(FichierIllisibleException::class);
});
