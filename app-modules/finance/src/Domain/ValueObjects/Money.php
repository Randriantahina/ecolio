<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\ValueObjects;

use Modules\Finance\Domain\Exceptions\InvalidMoneyException;

/**
 * Whole ariary, never a float: the ariary has no usable subunit and float rounding would
 * break the rule that a payment total equals the sum of its lines (spec section 8).
 */
final readonly class Money
{
    private function __construct(private int $amount)
    {
        // Negative values are rejected so that an overpayment or a balance below zero
        // cannot exist as a state; callers must handle the excess explicitly.
        if ($amount < 0) {
            throw InvalidMoneyException::negative($amount);
        }
    }

    public static function ariary(int $amount): self
    {
        return new self($amount);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /** @param  iterable<Money>  $amounts */
    public static function sum(iterable $amounts): self
    {
        $total = self::zero();
        foreach ($amounts as $amount) {
            $total = $total->plus($amount);
        }

        return $total;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function plus(self $other): self
    {
        return new self($this->amount + $other->amount);
    }

    public function minus(self $other): self
    {
        return new self($this->amount - $other->amount);
    }

    public function min(self $other): self
    {
        return $this->amount <= $other->amount ? $this : $other;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->amount > $other->amount;
    }

    public function isLessThan(self $other): bool
    {
        return $this->amount < $other->amount;
    }
}
