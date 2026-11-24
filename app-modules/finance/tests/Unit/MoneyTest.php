<?php

declare(strict_types=1);

use Modules\Finance\Domain\Exceptions\InvalidMoneyException;
use Modules\Finance\Domain\ValueObjects\Money;

it('holds an amount in whole ariary', function () {
    expect(Money::ariary(150_000)->amount())->toBe(150_000);
});

it('refuses a negative amount, since a balance below zero is a bug not a state', function () {
    Money::ariary(-1);
})->throws(InvalidMoneyException::class);

it('has a zero constant', function () {
    expect(Money::zero()->amount())->toBe(0)
        ->and(Money::zero()->isZero())->toBeTrue();
});

it('adds two amounts into a new value and leaves the originals untouched', function () {
    $a = Money::ariary(100);
    $b = Money::ariary(50);

    expect($a->plus($b)->amount())->toBe(150)
        ->and($a->amount())->toBe(100)
        ->and($b->amount())->toBe(50);
});

it('subtracts a smaller amount', function () {
    expect(Money::ariary(100)->minus(Money::ariary(40))->amount())->toBe(60);
});

it('refuses a subtraction that would go below zero, so an overpayment cannot be recorded', function () {
    Money::ariary(40)->minus(Money::ariary(100));
})->throws(InvalidMoneyException::class);

it('compares by value', function () {
    $a = Money::ariary(100);

    expect($a->equals(Money::ariary(100)))->toBeTrue()
        ->and($a->equals(Money::ariary(101)))->toBeFalse()
        ->and($a->isGreaterThan(Money::ariary(99)))->toBeTrue()
        ->and($a->isGreaterThan(Money::ariary(100)))->toBeFalse()
        ->and($a->isLessThan(Money::ariary(101)))->toBeTrue();
});

it('returns the smaller of two amounts, used to cap a payment line at the remaining balance', function () {
    expect(Money::ariary(30)->min(Money::ariary(80))->amount())->toBe(30)
        ->and(Money::ariary(80)->min(Money::ariary(30))->amount())->toBe(30);
});

it('sums a list, and an empty list is zero', function () {
    expect(Money::sum([Money::ariary(10), Money::ariary(20), Money::ariary(5)])->amount())->toBe(35)
        ->and(Money::sum([])->isZero())->toBeTrue();
});
