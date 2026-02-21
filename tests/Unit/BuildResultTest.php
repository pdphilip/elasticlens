<?php

declare(strict_types=1);

use PDPhilip\ElasticLens\Engine\BuildResult;

it('creates with initial values', function () {
    $result = new BuildResult(1, 'App\\Models\\User', 0);

    expect($result->id)->toBe(1)
        ->and($result->model)->toBe('App\\Models\\User')
        ->and($result->success)->toBeFalse()
        ->and($result->skipped)->toBeFalse()
        ->and($result->msg)->toBe('')
        ->and($result->map)->toBe([]);
});

it('marks as successful', function () {
    $result = new BuildResult(1, 'App\\Models\\User', 0);
    $result->successful('Done');

    expect($result->success)->toBeTrue()
        ->and($result->details)->toBe('Done')
        ->and($result->took)->toHaveKeys(['ms', 'sec', 'min']);
});

it('marks as failed', function () {
    $result = new BuildResult(1, 'App\\Models\\User', 0);
    $result->setMessage('Error', 'Something broke');
    $result->failed();

    expect($result->success)->toBeFalse()
        ->and($result->msg)->toBe('Error')
        ->and($result->details)->toBe('Something broke')
        ->and($result->took)->toHaveKeys(['ms', 'sec', 'min']);
});

it('sets map data', function () {
    $result = new BuildResult(1, 'App\\Models\\User', 0);
    $result->setMap(['name' => 'John', 'email' => 'john@test.com']);

    expect($result->map)->toBe(['name' => 'John', 'email' => 'john@test.com']);
});

it('attaches migration version', function () {
    $result = new BuildResult(1, 'App\\Models\\User', 0);
    $result->attachMigrationVersion('v1.2');

    expect($result->migration_version)->toBe('v1.2');
});

it('converts to array', function () {
    $result = new BuildResult(1, 'App\\Models\\User', 0);
    $result->setMap(['name' => 'John']);
    $result->successful('Done');

    $array = $result->toArray();

    expect($array)->toHaveKeys(['id', 'model', 'success', 'msg', 'details', 'map', 'migration_version', 'took'])
        ->and($array['id'])->toBe(1)
        ->and($array['success'])->toBeTrue()
        ->and($array['map'])->toBe(['name' => 'John']);
});

it('returns self from fluent methods', function () {
    $result = new BuildResult(1, 'App\\Models\\User', 0);

    expect($result->successful())->toBe($result)
        ->and($result->failed())->toBe($result)
        ->and($result->attachMigrationVersion('v1'))->toBe($result);
});
