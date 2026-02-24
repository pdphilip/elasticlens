<?php

declare(strict_types=1);

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PDPhilip\ElasticLens\Config\IndexConfig;
use PDPhilip\ElasticLens\Engine\RecordBuilder;
use PDPhilip\ElasticLens\Tests\Models\Indexes\IndexedUser;
use PDPhilip\ElasticLens\Tests\Models\Profile;
use PDPhilip\ElasticLens\Tests\Models\User;
use PDPhilip\ElasticLens\Tests\Models\UserLog;
use PDPhilip\Elasticsearch\Eloquent\ElasticCollection;

beforeEach(function () {
    IndexConfig::clearCache();
    User::$excludeIndexUsing = null;
    User::executeSchema();
    Profile::executeSchema();
    UserLog::executeSchema();
    IndexedUser::executeSchema();
});

function seedUsers(int $count = 3): void
{
    for ($i = 1; $i <= $count; $i++) {
        $user = User::create([
            'name' => "User $i",
            'email' => "lensbuilder$i@test.com",
            'status' => 'active',
            'age' => 20 + $i,
        ]);
        RecordBuilder::build(IndexedUser::class, $user->id, 'test');
    }
    sleep(1);
}

// ======================================================================
// get()
// ======================================================================

it('get returns empty ElasticCollection when no results', function () {
    $results = IndexedUser::query()->get();

    expect($results)->toBeInstanceOf(ElasticCollection::class)
        ->and($results)->toBeEmpty();
});

it('get returns index models by default', function () {
    seedUsers(3);

    $results = IndexedUser::query()->get();

    expect($results)->toHaveCount(3)
        ->and($results->first())->toBeInstanceOf(IndexedUser::class);
});

// ======================================================================
// getIndex()
// ======================================================================

it('getIndex returns empty ElasticCollection when no results', function () {
    $results = IndexedUser::query()->getIndex();

    expect($results)->toBeInstanceOf(ElasticCollection::class)
        ->and($results)->toBeEmpty();
});

it('getIndex returns index models', function () {
    seedUsers(3);

    $results = IndexedUser::query()->getIndex();

    expect($results)->toHaveCount(3)
        ->and($results->first())->toBeInstanceOf(IndexedUser::class);
});

// ======================================================================
// getBase()
// ======================================================================

it('getBase returns empty collection when no results', function () {
    $results = IndexedUser::query()->getBase();

    expect($results)->toBeInstanceOf(Collection::class)
        ->and($results)->toBeEmpty();
});

it('getBase returns base models', function () {
    seedUsers(3);

    $results = IndexedUser::query()->getBase();

    expect($results)->toHaveCount(3)
        ->and($results->first())->toBeInstanceOf(User::class);
});

// ======================================================================
// viaIndex get()
// ======================================================================

it('viaIndex get returns empty collection when no results', function () {
    $results = User::viaIndex()->get();

    expect($results)->toBeEmpty();
});

it('viaIndex get returns base models', function () {
    seedUsers(3);

    $results = User::viaIndex()->get();

    expect($results)->toHaveCount(3)
        ->and($results->first())->toBeInstanceOf(User::class);
});

// ======================================================================
// first()
// ======================================================================

it('first returns null when no results', function () {
    $result = IndexedUser::query()->first();

    expect($result)->toBeNull();
});

it('first returns index model by default', function () {
    seedUsers(1);

    $result = IndexedUser::query()->first();

    expect($result)->toBeInstanceOf(IndexedUser::class);
});

// ======================================================================
// firstIndex()
// ======================================================================

it('firstIndex returns null when no results', function () {
    $result = IndexedUser::query()->firstIndex();

    expect($result)->toBeNull();
});

it('firstIndex returns index model', function () {
    seedUsers(1);

    $result = IndexedUser::query()->firstIndex();

    expect($result)->toBeInstanceOf(IndexedUser::class);
});

// ======================================================================
// firstBase()
// ======================================================================

it('firstBase returns null when no results', function () {
    $result = IndexedUser::query()->firstBase();

    expect($result)->toBeNull();
});

it('firstBase returns base model', function () {
    seedUsers(1);

    $result = IndexedUser::query()->firstBase();

    expect($result)->toBeInstanceOf(User::class);
});

// ======================================================================
// viaIndex first()
// ======================================================================

it('viaIndex first returns null when no results', function () {
    $result = User::viaIndex()->first();

    expect($result)->toBeNull();
});

it('viaIndex first returns base model', function () {
    seedUsers(1);

    $result = User::viaIndex()->first();

    expect($result)->toBeInstanceOf(User::class);
});

// ======================================================================
// paginate()
// ======================================================================

it('paginate returns empty paginator when no results', function () {
    $results = IndexedUser::query()->paginate(10);

    expect($results)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($results->total())->toBe(0)
        ->and($results->items())->toBeEmpty();
});

it('paginate returns index models by default', function () {
    seedUsers(3);

    $results = IndexedUser::query()->paginate(10);

    expect($results)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($results->total())->toBe(3)
        ->and($results->items()[0])->toBeInstanceOf(IndexedUser::class);
});

// ======================================================================
// paginateIndex()
// ======================================================================

it('paginateIndex returns empty paginator when no results', function () {
    $results = IndexedUser::query()->paginateIndex(10);

    expect($results)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($results->total())->toBe(0)
        ->and($results->items())->toBeEmpty();
});

it('paginateIndex returns index models', function () {
    seedUsers(3);

    $results = IndexedUser::query()->paginateIndex(10);

    expect($results)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($results->total())->toBe(3)
        ->and($results->items()[0])->toBeInstanceOf(IndexedUser::class);
});

// ======================================================================
// paginateBase()
// ======================================================================

it('paginateBase returns empty paginator when no results', function () {
    $results = IndexedUser::query()->paginateBase(10);

    expect($results)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($results->total())->toBe(0)
        ->and($results->items())->toBeEmpty();
});

it('paginateBase returns base models', function () {
    seedUsers(3);

    $results = IndexedUser::query()->paginateBase(10);

    expect($results)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($results->total())->toBe(3)
        ->and($results->items()[0])->toBeInstanceOf(User::class);
});

// ======================================================================
// viaIndex paginate()
// ======================================================================

it('viaIndex paginate returns empty paginator when no results', function () {
    $results = User::viaIndex()->paginate(10);

    expect($results)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($results->total())->toBe(0)
        ->and($results->items())->toBeEmpty();
});

it('viaIndex paginate returns base models', function () {
    seedUsers(3);

    $results = User::viaIndex()->paginate(10);

    expect($results)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($results->total())->toBe(3)
        ->and($results->items()[0])->toBeInstanceOf(User::class);
});
