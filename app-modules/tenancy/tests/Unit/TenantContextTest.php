<?php

declare(strict_types=1);

use Modules\Tenancy\Domain\Exceptions\TenantNotResolvedException;
use Modules\Tenancy\Domain\TenantContext;

it('starts unresolved so that nothing is readable by accident', function () {
    $context = new TenantContext;

    expect($context->isResolved())->toBeFalse()
        ->and($context->isPlatform())->toBeFalse();
});

it('resolves to a school', function () {
    $context = new TenantContext;
    $context->forEcole(7);

    expect($context->isResolved())->toBeTrue()
        ->and($context->ecoleId())->toBe(7)
        ->and($context->isPlatform())->toBeFalse();
});

it('resolves to the platform for super admins, with no school id', function () {
    $context = new TenantContext;
    $context->asPlatform();

    expect($context->isResolved())->toBeTrue()
        ->and($context->isPlatform())->toBeTrue()
        ->and($context->ecoleId())->toBeNull();
});

it('refuses to hand out a school id when unresolved', function () {
    (new TenantContext)->requireEcoleId();
})->throws(TenantNotResolvedException::class);

it('runs a callback in another school then restores the previous context', function () {
    $context = new TenantContext;
    $context->forEcole(1);

    $inside = $context->runAs(2, fn () => $context->ecoleId());

    expect($inside)->toBe(2)
        ->and($context->ecoleId())->toBe(1);
});

it('restores the previous context even when the callback throws', function () {
    $context = new TenantContext;
    $context->forEcole(1);

    try {
        $context->runAs(2, fn () => throw new RuntimeException('boom'));
    } catch (RuntimeException) {
    }

    expect($context->ecoleId())->toBe(1);
});
