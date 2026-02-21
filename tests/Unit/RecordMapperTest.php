<?php

declare(strict_types=1);

use PDPhilip\ElasticLens\Config\IndexConfig;
use PDPhilip\ElasticLens\Engine\RecordMapper;
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
});

it('maps base model fields according to field map', function () {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@test.com',
        'status' => 'active',
        'age' => 30,
    ]);

    $config = IndexConfig::for(IndexedUser::class);
    $data = RecordMapper::map($user, $config);

    expect($data)->not->toBeNull()
        ->and($data['id'])->toBe($user->id)
        ->and($data['name'])->toBe('John Doe')
        ->and($data['email'])->toBe('john@test.com')
        ->and($data['status'])->toBe('active')
        ->and($data['age'])->toBe(30);
});

it('maps all attributes when no field map defined', function () {
    $company = Company::create([
        'name' => 'Acme Corp',
        'industry' => 'Tech',
    ]);

    $config = IndexConfig::for(IndexedCompany::class);
    $data = RecordMapper::map($company, $config);

    expect($data)->not->toBeNull()
        ->and($data['id'])->toBe($company->id)
        ->and($data['name'])->toBe('Acme Corp')
        ->and($data['industry'])->toBe('Tech');
});

it('embeds hasOne relationship', function () {
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

    $config = IndexConfig::for(IndexedUser::class);
    $data = RecordMapper::map($user, $config);

    expect($data['profile'])->toBeArray()
        ->and($data['profile']['bio'])->toBe('Developer')
        ->and($data['profile']['website'])->toBe('jane.dev');
});

it('embeds hasMany relationship', function () {
    $user = User::create([
        'name' => 'Jane',
        'email' => 'jane@test.com',
        'status' => 'active',
        'age' => 25,
    ]);
    UserLog::create(['user_id' => $user->id, 'action' => 'login', 'details' => 'From mobile']);
    UserLog::create(['user_id' => $user->id, 'action' => 'update', 'details' => 'Changed email']);

    $config = IndexConfig::for(IndexedUser::class);
    $data = RecordMapper::map($user, $config);

    expect($data['logs'])->toBeArray()
        ->and($data['logs'])->toHaveCount(2)
        ->and($data['logs'][0]['action'])->toBe('login')
        ->and($data['logs'][1]['action'])->toBe('update');
});

it('returns empty array for missing hasOne embed', function () {
    $user = User::create([
        'name' => 'Solo',
        'email' => 'solo@test.com',
        'status' => 'active',
        'age' => 40,
    ]);

    $config = IndexConfig::for(IndexedUser::class);
    $data = RecordMapper::map($user, $config);

    expect($data['profile'])->toBe([]);
});

it('returns empty array for missing hasMany embed', function () {
    $user = User::create([
        'name' => 'Solo',
        'email' => 'solo@test.com',
        'status' => 'active',
        'age' => 40,
    ]);

    $config = IndexConfig::for(IndexedUser::class);
    $data = RecordMapper::map($user, $config);

    expect($data['logs'])->toBe([]);
});

it('returns null when model excluded', function () {
    $user = User::create([
        'name' => 'Excluded',
        'email' => 'excluded@test.com',
        'status' => 'active',
        'age' => 50,
    ]);

    // Override excludeIndex on the instance
    $mock = Mockery::mock($user)->makePartial();
    $mock->shouldReceive('excludeIndex')->andReturn(true);

    $config = IndexConfig::for(IndexedUser::class);
    $data = RecordMapper::map($mock, $config);

    expect($data)->toBeNull();
});
