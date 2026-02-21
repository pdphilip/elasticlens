<?php

declare(strict_types=1);

use PDPhilip\ElasticLens\Config\IndexConfig;
use PDPhilip\ElasticLens\Engine\RecordBuilder;
use PDPhilip\ElasticLens\Tests\Models\Indexes\IndexedUser;
use PDPhilip\ElasticLens\Tests\Models\Profile;
use PDPhilip\ElasticLens\Tests\Models\User;
use PDPhilip\ElasticLens\Tests\Models\UserLog;

beforeEach(function () {
    IndexConfig::clearCache();
    User::$excludeIndexUsing = null;
    User::executeSchema();
    Profile::executeSchema();
    UserLog::executeSchema();
    IndexedUser::executeSchema();
});

it('resolves base model from index model', function () {
    $indexModel = new IndexedUser;

    expect($indexModel->getBaseModel())->toBe(User::class)
        ->and($indexModel->isBaseModelDefined())->toBeTrue();
});

it('returns field set from fieldMap', function () {
    $indexModel = new IndexedUser;
    $fields = $indexModel->getFieldSet();

    expect($fields)->toBeArray()
        ->and($fields)->toHaveKeys(['name', 'email', 'status', 'age']);
});

it('returns observer set', function () {
    $indexModel = new IndexedUser;
    $observers = $indexModel->getObserverSet();

    expect($observers)->toHaveKeys(['base', 'embedded'])
        ->and($observers['base'])->toBe(User::class);
});

it('accesses base model via attribute', function () {
    $user = User::create([
        'name' => 'Base Test',
        'email' => 'base@test.com',
        'status' => 'active',
        'age' => 30,
    ]);

    RecordBuilder::build(IndexedUser::class, $user->id, 'test');
    sleep(1);

    $index = IndexedUser::find($user->id);
    expect($index)->not->toBeNull();

    $base = $index->base;
    expect($base)->not->toBeNull()
        ->and($base)->toBeInstanceOf(User::class)
        ->and($base->name)->toBe('Base Test');
});

it('batch fetches base models without N+1', function () {
    for ($i = 1; $i <= 5; $i++) {
        $user = User::create([
            'name' => "User $i",
            'email' => "user$i@test.com",
            'status' => 'active',
            'age' => 20 + $i,
        ]);
        RecordBuilder::build(IndexedUser::class, $user->id, 'test');
    }

    sleep(1);

    $results = IndexedUser::query()->getBase();

    expect($results)->toHaveCount(5)
        ->and($results->first())->toBeInstanceOf(User::class);
});

it('asBase collection macro batch fetches', function () {
    for ($i = 1; $i <= 3; $i++) {
        $user = User::create([
            'name' => "User $i",
            'email' => "batch$i@test.com",
            'status' => 'active',
            'age' => 20 + $i,
        ]);
        RecordBuilder::build(IndexedUser::class, $user->id, 'test');
    }

    sleep(1);

    $indexResults = IndexedUser::all();
    $baseModels = $indexResults->asBase();

    expect($baseModels)->toHaveCount(3)
        ->and($baseModels->first())->toBeInstanceOf(User::class);
});

it('returns empty collection for empty getBase', function () {
    $results = IndexedUser::query()->getBase();

    expect($results)->toBeEmpty();
});

it('indexBuild static method works', function () {
    $user = User::create([
        'name' => 'Static Build',
        'email' => 'static@test.com',
        'status' => 'active',
        'age' => 30,
    ]);

    $result = IndexedUser::indexBuild($user->id, 'test');

    expect($result->success)->toBeTrue();

    sleep(1);

    $index = IndexedUser::find($user->id);
    expect($index)->not->toBeNull()
        ->and($index->name)->toBe('Static Build');
});

it('indexRebuild instance method works', function () {
    $user = User::create([
        'name' => 'Instance Rebuild',
        'email' => 'rebuild@test.com',
        'status' => 'active',
        'age' => 30,
    ]);

    RecordBuilder::build(IndexedUser::class, $user->id, 'test');
    sleep(1);

    $index = IndexedUser::find($user->id);
    expect($index)->not->toBeNull();

    $result = $index->indexRebuild('test');
    expect($result->success)->toBeTrue();
});
