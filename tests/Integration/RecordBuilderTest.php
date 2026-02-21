<?php

declare(strict_types=1);

use PDPhilip\ElasticLens\Config\IndexConfig;
use PDPhilip\ElasticLens\Engine\RecordBuilder;
use PDPhilip\ElasticLens\Tests\Models\Company;
use PDPhilip\ElasticLens\Tests\Models\Indexes\IndexedCompany;
use PDPhilip\ElasticLens\Tests\Models\Indexes\IndexedUser;
use PDPhilip\ElasticLens\Tests\Models\Profile;
use PDPhilip\ElasticLens\Tests\Models\User;
use PDPhilip\ElasticLens\Tests\Models\UserLog;

beforeEach(function () {
    IndexConfig::clearCache();
    User::executeSchema();
    Profile::executeSchema();
    UserLog::executeSchema();
    Company::executeSchema();
    IndexedUser::executeSchema();
    IndexedCompany::executeSchema();
});

it('builds an index record from a base model', function () {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@test.com',
        'status' => 'active',
        'age' => 30,
    ]);

    $result = RecordBuilder::build(IndexedUser::class, $user->id, 'test');

    expect($result->success)->toBeTrue()
        ->and($result->id)->toBe($user->id)
        ->and($result->map['name'])->toBe('John Doe');

    sleep(1);

    $index = IndexedUser::find($user->id);
    expect($index)->not->toBeNull()
        ->and($index->name)->toBe('John Doe')
        ->and($index->email)->toBe('john@test.com');
});

it('builds with embedded relationships', function () {
    $user = User::create([
        'name' => 'Jane',
        'email' => 'jane@test.com',
        'status' => 'active',
        'age' => 25,
    ]);
    Profile::create([
        'user_id' => $user->id,
        'bio' => 'Developer',
        'website' => 'jane.dev',
    ]);
    UserLog::create(['user_id' => $user->id, 'action' => 'signup']);

    $result = RecordBuilder::build(IndexedUser::class, $user->id, 'test');

    expect($result->success)->toBeTrue();

    sleep(1);

    $index = IndexedUser::find($user->id);
    expect($index)->not->toBeNull()
        ->and($index->profile)->toBeArray()
        ->and($index->profile['bio'])->toBe('Developer')
        ->and($index->logs)->toHaveCount(1)
        ->and($index->logs[0]['action'])->toBe('signup');
});

it('updates existing index record', function () {
    $user = User::create([
        'name' => 'Original',
        'email' => 'orig@test.com',
        'status' => 'active',
        'age' => 30,
    ]);

    RecordBuilder::build(IndexedUser::class, $user->id, 'test');
    sleep(1);

    $user->update(['name' => 'Updated']);
    $result = RecordBuilder::build(IndexedUser::class, $user->id, 'test');

    expect($result->success)->toBeTrue();

    sleep(1);

    $index = IndexedUser::find($user->id);
    expect($index->name)->toBe('Updated');
    expect(IndexedUser::count())->toBe(1);
});

it('fails when base model not found', function () {
    $result = RecordBuilder::build(IndexedUser::class, 99999, 'test');

    expect($result->success)->toBeFalse()
        ->and($result->msg)->toBe('BaseModel not found');
});

it('dry run maps without saving', function () {
    $user = User::withoutEvents(function () {
        return User::create([
            'name' => 'Dry Run',
            'email' => 'dry@test.com',
            'status' => 'active',
            'age' => 35,
        ]);
    });

    $result = RecordBuilder::dryRun(IndexedUser::class, $user->id);

    expect($result->success)->toBeTrue()
        ->and($result->map['name'])->toBe('Dry Run');

    sleep(1);

    expect(IndexedUser::find($user->id))->toBeNull();
});

it('deletes index record and build state', function () {
    $user = User::create([
        'name' => 'Delete Me',
        'email' => 'delete@test.com',
        'status' => 'active',
        'age' => 30,
    ]);

    RecordBuilder::build(IndexedUser::class, $user->id, 'test');
    sleep(1);
    expect(IndexedUser::find($user->id))->not->toBeNull();

    RecordBuilder::delete(IndexedUser::class, $user->id);
    sleep(1);
    expect(IndexedUser::find($user->id))->toBeNull();
});

it('finds soft-deleted models via withTrashed', function () {
    $user = User::create([
        'name' => 'Soft Deleted',
        'email' => 'soft@test.com',
        'status' => 'active',
        'age' => 30,
    ]);

    RecordBuilder::build(IndexedUser::class, $user->id, 'test');
    $user->delete();

    // User is soft deleted — RecordBuilder should still find it
    $result = RecordBuilder::build(IndexedUser::class, $user->id, 'test (soft deleted)');

    expect($result->success)->toBeTrue()
        ->and($result->map['name'])->toBe('Soft Deleted');
});

it('builds index for model without field map', function () {
    $company = Company::create([
        'name' => 'Acme Corp',
        'industry' => 'Tech',
    ]);

    $result = RecordBuilder::build(IndexedCompany::class, $company->id, 'test');

    expect($result->success)->toBeTrue();

    sleep(1);

    $index = IndexedCompany::find($company->id);
    expect($index)->not->toBeNull()
        ->and($index->name)->toBe('Acme Corp')
        ->and($index->industry)->toBe('Tech');
});

it('custom build runs callback', function () {
    $user = User::create([
        'name' => 'Custom',
        'email' => 'custom@test.com',
        'status' => 'active',
        'age' => 30,
    ]);

    $called = false;
    $result = RecordBuilder::customBuild(IndexedUser::class, $user->id, 'test', function () use (&$called) {
        $called = true;
    });

    expect($called)->toBeTrue()
        ->and($result->success)->toBeTrue()
        ->and($result->details)->toBe('Custom Build Passed');
});

it('custom build catches callback exceptions', function () {
    $user = User::create([
        'name' => 'Fail',
        'email' => 'fail@test.com',
        'status' => 'active',
        'age' => 30,
    ]);

    $result = RecordBuilder::customBuild(IndexedUser::class, $user->id, 'test', function () {
        throw new Exception('Boom');
    });

    expect($result->success)->toBeFalse()
        ->and($result->msg)->toBe('Custom Build Failed');
});
