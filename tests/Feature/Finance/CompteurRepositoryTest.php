<?php

declare(strict_types=1);

use App\Domain\Finance\Contracts\CompteurRepository;
use App\Domain\Platform\Models\Ecole;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->compteurs = app(CompteurRepository::class);
    $this->ecoleA = Ecole::factory()->create();
    $this->ecoleB = Ecole::factory()->create();
});

it('starts a new counter at one', function () {
    expect($this->compteurs->nextValue($this->ecoleA->id, 'recu:2026'))->toBe(1);
});

it('hands out consecutive numbers with no gap', function () {
    $values = array_map(fn () => $this->compteurs->nextValue($this->ecoleA->id, 'recu:2026'), range(1, 5));

    expect($values)->toBe([1, 2, 3, 4, 5]);
});

it('keeps one sequence per key, so receipts and matricules do not share numbers', function () {
    $this->compteurs->nextValue($this->ecoleA->id, 'recu:2026');
    $this->compteurs->nextValue($this->ecoleA->id, 'recu:2026');

    expect($this->compteurs->nextValue($this->ecoleA->id, 'matricule'))->toBe(1)
        ->and($this->compteurs->nextValue($this->ecoleA->id, 'recu:2027'))->toBe(1);
});

it('keeps one sequence per school: school A never advances school B', function () {
    $this->compteurs->nextValue($this->ecoleA->id, 'recu:2026');
    $this->compteurs->nextValue($this->ecoleA->id, 'recu:2026');

    expect($this->compteurs->nextValue($this->ecoleB->id, 'recu:2026'))->toBe(1);
});

it('stores a single row per school and key', function () {
    $this->compteurs->nextValue($this->ecoleA->id, 'recu:2026');
    $this->compteurs->nextValue($this->ecoleA->id, 'recu:2026');

    expect(DB::table('compteurs')->where('ecole_id', $this->ecoleA->id)->count())->toBe(1);
});

it('forbids two rows for the same school and key at database level', function () {
    DB::table('compteurs')->insert(['ecole_id' => $this->ecoleA->id, 'cle' => 'k', 'valeur' => 1]);

    DB::table('compteurs')->insert(['ecole_id' => $this->ecoleA->id, 'cle' => 'k', 'valeur' => 1]);
})->throws(QueryException::class);

it('does not consume a number when the surrounding transaction rolls back, so a failed payment leaves no gap', function () {
    $this->compteurs->nextValue($this->ecoleA->id, 'recu:2026');

    try {
        DB::transaction(function () {
            $this->compteurs->nextValue($this->ecoleA->id, 'recu:2026');
            throw new RuntimeException('payment failed after taking a number');
        });
    } catch (RuntimeException) {
    }

    expect($this->compteurs->nextValue($this->ecoleA->id, 'recu:2026'))->toBe(2);
});

it('refuses an empty key', function () {
    $this->compteurs->nextValue($this->ecoleA->id, '');
})->throws(InvalidArgumentException::class);
