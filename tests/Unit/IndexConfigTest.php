<?php

declare(strict_types=1);

use PDPhilip\ElasticLens\Config\IndexConfig;
use PDPhilip\ElasticLens\Tests\Models\Indexes\IndexedCompany;
use PDPhilip\ElasticLens\Tests\Models\Indexes\IndexedUser;
use PDPhilip\ElasticLens\Tests\Models\User;

beforeEach(function () {
    IndexConfig::clearCache();
});

it('resolves index config from index model class', function () {
    $config = IndexConfig::for(IndexedUser::class);

    expect($config->indexModel)->toBe(IndexedUser::class)
        ->and($config->indexModelName)->toBe('IndexedUser')
        ->and($config->baseModel)->toBe(User::class)
        ->and($config->baseModelName)->toBe('User')
        ->and($config->baseModelPrimaryKey)->toBe('id')
        ->and($config->baseModelDefined)->toBeTrue()
        ->and($config->baseModelIndexable)->toBeTrue();
});

it('extracts field map from index model', function () {
    $config = IndexConfig::for(IndexedUser::class);

    expect($config->fieldMap)->toBeArray()
        ->and($config->fieldMap)->toHaveKeys(['name', 'email', 'status', 'age', 'created_at', 'profile', 'logs']);
});

it('extracts relationships from index model', function () {
    $config = IndexConfig::for(IndexedUser::class);

    expect($config->relationships)->toBeArray()
        ->and($config->relationships)->toHaveKeys(['profile', 'logs'])
        ->and($config->relationships['profile']['type'])->toBe('hasOne')
        ->and($config->relationships['logs']['type'])->toBe('hasMany');
});

it('caches config per class', function () {
    $first = IndexConfig::for(IndexedUser::class);
    $second = IndexConfig::for(IndexedUser::class);

    expect($first)->toBe($second);
});

it('caches different classes independently', function () {
    $user = IndexConfig::for(IndexedUser::class);
    $company = IndexConfig::for(IndexedCompany::class);

    expect($user)->not->toBe($company)
        ->and($user->baseModelName)->toBe('User')
        ->and($company->baseModelName)->toBe('Company');
});

it('clears cache', function () {
    $first = IndexConfig::for(IndexedUser::class);
    IndexConfig::clearCache();
    $second = IndexConfig::for(IndexedUser::class);

    expect($first)->not->toBe($second);
});

it('throws for non-existent class', function () {
    IndexConfig::for('NonExistent\\Model');
})->throws(Exception::class);

it('throws for non-IndexModel class', function () {
    IndexConfig::for(User::class);
})->throws(Exception::class);

// Soft delete config resolution
it('defaults soft delete to global config', function () {
    $config = IndexConfig::for(IndexedUser::class);

    expect($config->indexSoftDeletes)->toBeNull()
        ->and($config->shouldIndexSoftDeletes())->toBeFalse();
});

it('respects global soft delete config when true', function () {
    config()->set('elasticlens.index_soft_deletes', true);
    IndexConfig::clearCache();
    $config = IndexConfig::for(IndexedUser::class);

    expect($config->shouldIndexSoftDeletes())->toBeTrue();
});
