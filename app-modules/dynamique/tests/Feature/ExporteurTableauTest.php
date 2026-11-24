<?php

declare(strict_types=1);

use Modules\Dynamique\Domain\Contracts\ExporteurTableau;
use PhpOffice\PhpSpreadsheet\IOFactory;

beforeEach(function () {
    $this->exporteur = app(ExporteurTableau::class);
    $this->entetes = ['Matricule', 'Nom', 'Montant'];
    $this->lignes = [['M-001', 'Rakoto', 50000], ['M-002', 'Rabe "Jean"', 0]];
});

it('writes an xlsx whose first row is the headings and whose cells keep their types', function () {
    $chemin = tempnam(sys_get_temp_dir(), 'ecolio').'.xlsx';
    file_put_contents($chemin, $this->exporteur->contenu($this->entetes, $this->lignes, 'xlsx'));

    $feuille = IOFactory::load($chemin)->getActiveSheet()->toArray();
    unlink($chemin);

    expect($feuille[0])->toBe(['Matricule', 'Nom', 'Montant'])
        ->and($feuille[1])->toEqual(['M-001', 'Rakoto', 50000])
        ->and($feuille[2][1])->toBe('Rabe "Jean"');
});

it('writes a csv a French Excel opens correctly: semicolon separator and UTF-8 BOM', function () {
    $csv = $this->exporteur->contenu(['Élève', 'Note'], [['Rakoto Éric', 12]], 'csv');

    expect($csv)->toStartWith("\xEF\xBB\xBF")->and($csv)->toContain('Élève;Note')->and($csv)->toContain('"Rakoto Éric";12');
});

it('refuses an unknown format', function () {
    expect(fn () => $this->exporteur->contenu($this->entetes, $this->lignes, 'pdf'))->toThrow(InvalidArgumentException::class);
});

it('does not let a cell run as a spreadsheet formula', function () {
    $csv = $this->exporteur->contenu(['Nom'], [['=HYPERLINK("http://x")'], ['+1'], ['@cmd']], 'csv');

    expect($csv)->toContain("'=HYPERLINK")->and($csv)->toContain("'+1")->and($csv)->toContain("'@cmd");
});

it('names the download with the format extension', function () {
    expect($this->exporteur->nomDeFichier('eleves', 'xlsx'))->toMatch('/^eleves-\d{4}-\d{2}-\d{2}\.xlsx$/');
});
