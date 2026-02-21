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

afterEach(function () {
    User::$excludeIndexUsing = null;
});

// ======================================================================
// RecordBuilder — Excluded records
// ======================================================================

it('skips indexing when excludeIndex returns true', function () {
    User::$excludeIndexUsing = fn ($user) => $user->is_admin;

    $admin = User::withoutEvents(function () {
        return User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'status' => 'active',
            'is_admin' => true,
            'age' => 35,
        ]);
    });

    $result = RecordBuilder::build(IndexedUser::class, $admin->id, 'test');

    expect($result->skipped)->toBeTrue()
        ->and($result->success)->toBeFalse()
        ->and($result->map)->toBeEmpty();

    sleep(1);
    expect(IndexedUser::find($admin->id))->toBeNull();
});

it('indexes non-excluded records normally', function () {
    User::$excludeIndexUsing = fn ($user) => $user->is_admin;

    $user = User::withoutEvents(function () {
        return User::create([
            'name' => 'Regular User',
            'email' => 'regular@test.com',
            'status' => 'active',
            'is_admin' => false,
            'age' => 28,
        ]);
    });

    $result = RecordBuilder::build(IndexedUser::class, $user->id, 'test');

    expect($result->success)->toBeTrue()
        ->and($result->skipped)->toBeFalse();

    sleep(1);
    expect(IndexedUser::find($user->id))->not->toBeNull();
});

it('removes stale index when model becomes excluded', function () {
    // First, index the user normally (no exclusion)
    $user = User::withoutEvents(function () {
        return User::create([
            'name' => 'Promoted User',
            'email' => 'promoted@test.com',
            'status' => 'active',
            'is_admin' => false,
            'age' => 30,
        ]);
    });

    $result = RecordBuilder::build(IndexedUser::class, $user->id, 'test');
    expect($result->success)->toBeTrue();
    sleep(1);
    expect(IndexedUser::find($user->id))->not->toBeNull();

    // Now make the user excluded (promoted to admin)
    User::$excludeIndexUsing = fn ($u) => $u->is_admin;
    $user->update(['is_admin' => true]);

    $result = RecordBuilder::build(IndexedUser::class, $user->id, 'test');

    expect($result->skipped)->toBeTrue();

    sleep(1);
    expect(IndexedUser::find($user->id))->toBeNull();
});

it('dry run returns skipped result for excluded model', function () {
    User::$excludeIndexUsing = fn ($user) => $user->is_admin;

    $admin = User::withoutEvents(function () {
        return User::create([
            'name' => 'Admin Dry',
            'email' => 'admindry@test.com',
            'status' => 'active',
            'is_admin' => true,
            'age' => 40,
        ]);
    });

    $result = RecordBuilder::dryRun(IndexedUser::class, $admin->id);

    expect($result->skipped)->toBeTrue()
        ->and($result->success)->toBeFalse();
});

// ======================================================================
// Full observer pipeline with exclusion
// ======================================================================

it('skips indexing via observer when model is excluded', function () {
    User::$excludeIndexUsing = fn ($user) => $user->is_admin;

    $admin = User::create([
        'name' => 'Admin Observer',
        'email' => 'adminobs@test.com',
        'status' => 'active',
        'is_admin' => true,
        'age' => 45,
    ]);

    sleep(1);
    expect(IndexedUser::find($admin->id))->toBeNull();
});

it('removes index via observer when model transitions to excluded', function () {
    User::$excludeIndexUsing = fn ($user) => $user->is_admin;

    // Create non-excluded user — observer indexes it
    $user = User::create([
        'name' => 'Soon Admin',
        'email' => 'soonadmin@test.com',
        'status' => 'active',
        'is_admin' => false,
        'age' => 32,
    ]);

    sleep(2);
    expect(IndexedUser::find($user->id))->not->toBeNull();

    // Promote to admin — observer triggers rebuild, exclusion kicks in
    $user->update(['is_admin' => true]);

    sleep(2);
    expect(IndexedUser::find($user->id))->toBeNull();
});

it('re-indexes via observer when model transitions from excluded to included', function () {
    User::$excludeIndexUsing = fn ($user) => $user->is_admin;

    // Create excluded user
    $admin = User::create([
        'name' => 'Demoted Admin',
        'email' => 'demoted@test.com',
        'status' => 'active',
        'is_admin' => true,
        'age' => 38,
    ]);

    sleep(1);
    expect(IndexedUser::find($admin->id))->toBeNull();

    // Demote — no longer excluded
    $admin->update(['is_admin' => false]);

    sleep(1);
    $index = IndexedUser::find($admin->id);
    expect($index)->not->toBeNull()
        ->and($index->name)->toBe('Demoted Admin');
});
