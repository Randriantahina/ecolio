<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Platform\Domain\Models\Ecole;
use Modules\Tenancy\Domain\Exceptions\CrossTenantWriteException;
use Modules\Tenancy\Domain\Exceptions\TenantNotResolvedException;
use Modules\Tenancy\Domain\TenantContext;
use Modules\Tenancy\Tests\Support\TenantProbe;

uses(RefreshDatabase::class);

beforeEach(function () {
    Schema::create('tenant_probes', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('ecole_id');
        $table->string('label');
    });

    $this->ecoleA = Ecole::factory()->create();
    $this->ecoleB = Ecole::factory()->create();
    $this->tenant = app(TenantContext::class);
});

it('only returns rows of the current school', function () {
    $this->tenant->runAs($this->ecoleA->id, fn () => TenantProbe::create(['label' => 'a']));
    $this->tenant->runAs($this->ecoleB->id, fn () => TenantProbe::create(['label' => 'b']));

    $labels = $this->tenant->runAs($this->ecoleA->id, fn () => TenantProbe::pluck('label')->all());

    expect($labels)->toBe(['a']);
});

it('fills ecole_id from the current school on create', function () {
    $probe = $this->tenant->runAs($this->ecoleA->id, fn () => TenantProbe::create(['label' => 'a']));

    expect($probe->ecole_id)->toBe($this->ecoleA->id);
});

it('cannot find a row of another school by primary key', function () {
    $foreign = $this->tenant->runAs($this->ecoleB->id, fn () => TenantProbe::create(['label' => 'b']));

    $found = $this->tenant->runAs($this->ecoleA->id, fn () => TenantProbe::find($foreign->id));

    expect($found)->toBeNull();
});

it('cannot update or delete rows of another school', function () {
    $foreign = $this->tenant->runAs($this->ecoleB->id, fn () => TenantProbe::create(['label' => 'b']));

    $this->tenant->runAs($this->ecoleA->id, function () {
        TenantProbe::query()->update(['label' => 'hacked']);
        TenantProbe::query()->delete();
    });

    expect(TenantProbe::withoutGlobalScopes()->find($foreign->id)->label)->toBe('b');
});

it('refuses to write a row for another school while scoped', function () {
    $this->tenant->runAs($this->ecoleA->id, fn () => TenantProbe::create([
        'label' => 'x',
        'ecole_id' => $this->ecoleB->id,
    ]));
})->throws(CrossTenantWriteException::class);

it('fails closed when no school is resolved', function () {
    TenantProbe::query()->get();
})->throws(TenantNotResolvedException::class);

it('refuses to create a row when no school is resolved', function () {
    TenantProbe::create(['label' => 'orphan']);
})->throws(TenantNotResolvedException::class);

it('lets the platform read every school', function () {
    $this->tenant->runAs($this->ecoleA->id, fn () => TenantProbe::create(['label' => 'a']));
    $this->tenant->runAs($this->ecoleB->id, fn () => TenantProbe::create(['label' => 'b']));

    $this->tenant->asPlatform();

    expect(TenantProbe::count())->toBe(2);
});

it('requires an explicit school to create from the platform side', function () {
    $this->tenant->asPlatform();

    $probe = TenantProbe::create(['label' => 'seeded', 'ecole_id' => $this->ecoleA->id]);

    expect($probe->ecole_id)->toBe($this->ecoleA->id);
});

it('refuses a platform create without a school id', function () {
    $this->tenant->asPlatform();

    TenantProbe::create(['label' => 'orphan']);
})->throws(TenantNotResolvedException::class);
