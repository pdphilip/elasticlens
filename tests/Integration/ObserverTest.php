<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use PDPhilip\ElasticLens\Config\IndexConfig;
use PDPhilip\ElasticLens\Jobs\IndexBuildJob;
use PDPhilip\ElasticLens\Jobs\IndexDeletedJob;
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

// ======================================================================
// Job Dispatch
// ======================================================================

it('dispatches IndexBuildJob on model create', function () {
    Bus::fake();

    User::create([
        'name' => 'Observer Test',
        'email' => 'obs@test.com',
        'status' => 'active',
        'age' => 30,
    ]);

    Bus::assertDispatched(IndexBuildJob::class);
});

it('dispatches IndexBuildJob on model update', function () {
    Bus::fake();

    $user = User::withoutEvents(function () {
        return User::create([
            'name' => 'Before',
            'email' => 'before@test.com',
            'status' => 'active',
            'age' => 30,
        ]);
    });

    $user->update(['name' => 'After']);

    Bus::assertDispatched(IndexBuildJob::class);
});

it('dispatches IndexDeletedJob on hard delete', function () {
    Bus::fake();

    $user = User::withoutEvents(function () {
        return User::create([
            'name' => 'Delete Test',
            'email' => 'del@test.com',
            'status' => 'active',
            'age' => 30,
        ]);
    });

    $user->forceDelete();

    Bus::assertDispatched(IndexDeletedJob::class);
});

it('dispatches IndexDeletedJob on model without SoftDeletes', function () {
    Bus::fake();

    $company = Company::withoutEvents(function () {
        return Company::create([
            'name' => 'Delete Corp',
            'industry' => 'Tech',
        ]);
    });

    $company->delete();

    Bus::assertDispatched(IndexDeletedJob::class);
});

// ======================================================================
// Soft Delete Behavior
// ======================================================================

it('dispatches IndexDeletedJob on soft delete when config is false', function () {
    config()->set('elasticlens.index_soft_deletes', false);
    IndexConfig::clearCache();
    Bus::fake();

    $user = User::withoutEvents(function () {
        return User::create([
            'name' => 'Soft Del',
            'email' => 'soft@test.com',
            'status' => 'active',
            'age' => 30,
        ]);
    });

    $user->delete();

    Bus::assertDispatched(IndexDeletedJob::class);
    Bus::assertNotDispatched(IndexBuildJob::class);
});

it('dispatches IndexBuildJob on soft delete when config is true', function () {
    config()->set('elasticlens.index_soft_deletes', true);
    IndexConfig::clearCache();
    Bus::fake();

    $user = User::withoutEvents(function () {
        return User::create([
            'name' => 'Soft Keep',
            'email' => 'keep@test.com',
            'status' => 'active',
            'age' => 30,
        ]);
    });

    $user->delete();

    // Should NOT dispatch delete, should dispatch rebuild
    Bus::assertNotDispatched(IndexDeletedJob::class);
    Bus::assertDispatched(IndexBuildJob::class);
});

it('dispatches IndexBuildJob on restore', function () {
    Bus::fake();

    $user = User::withoutEvents(function () {
        $u = User::create([
            'name' => 'Restore Me',
            'email' => 'restore@test.com',
            'status' => 'active',
            'age' => 30,
        ]);
        $u->delete();

        return $u;
    });

    $user->restore();

    Bus::assertDispatched(IndexBuildJob::class);
});

it('dispatches IndexDeletedJob on force delete even with soft delete config', function () {
    config()->set('elasticlens.index_soft_deletes', true);
    IndexConfig::clearCache();
    Bus::fake();

    $user = User::withoutEvents(function () {
        return User::create([
            'name' => 'Force Del',
            'email' => 'force@test.com',
            'status' => 'active',
            'age' => 30,
        ]);
    });

    $user->forceDelete();

    Bus::assertDispatched(IndexDeletedJob::class);
});

// ======================================================================
// Full Sync Pipeline
// ======================================================================

it('syncs index on model save', function () {
    $user = User::create([
        'name' => 'Sync Test',
        'email' => 'sync@test.com',
        'status' => 'active',
        'age' => 28,
    ]);

    // Give ES a moment to index
    sleep(1);

    $index = IndexedUser::find($user->id);
    expect($index)->not->toBeNull()
        ->and($index->name)->toBe('Sync Test')
        ->and($index->email)->toBe('sync@test.com');
});

it('updates index on model update', function () {
    $user = User::create([
        'name' => 'Before Update',
        'email' => 'upd@test.com',
        'status' => 'active',
        'age' => 28,
    ]);

    sleep(1);

    $user->update(['name' => 'After Update']);

    sleep(1);

    $index = IndexedUser::find($user->id);
    expect($index->name)->toBe('After Update');
});

it('removes index on delete', function () {
    $user = User::create([
        'name' => 'Remove Test',
        'email' => 'remove@test.com',
        'status' => 'active',
        'age' => 28,
    ]);

    sleep(1);
    expect(IndexedUser::find($user->id))->not->toBeNull();

    $user->forceDelete();

    sleep(1);
    expect(IndexedUser::find($user->id))->toBeNull();
});

it('keeps index on soft delete when config enabled', function () {
    config()->set('elasticlens.index_soft_deletes', true);
    IndexConfig::clearCache();

    $user = User::create([
        'name' => 'Soft Keep Sync',
        'email' => 'softkeep@test.com',
        'status' => 'active',
        'age' => 33,
    ]);

    sleep(1);
    expect(IndexedUser::find($user->id))->not->toBeNull();

    $user->delete();

    sleep(1);

    $index = IndexedUser::find($user->id);
    expect($index)->not->toBeNull()
        ->and($index->name)->toBe('Soft Keep Sync');
});

it('restores index after restore', function () {
    config()->set('elasticlens.index_soft_deletes', true);
    IndexConfig::clearCache();

    $user = User::create([
        'name' => 'Restore Sync',
        'email' => 'restoresync@test.com',
        'status' => 'active',
        'age' => 29,
    ]);

    sleep(1);
    $user->delete();
    sleep(1);
    $user->restore();
    sleep(1);

    $index = IndexedUser::find($user->id);
    expect($index)->not->toBeNull()
        ->and($index->name)->toBe('Restore Sync');
});
