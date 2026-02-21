<?php

declare(strict_types=1);

use PDPhilip\ElasticLens\Lens;
use PDPhilip\ElasticLens\Tests\Models\Indexes\IndexedUser;
use PDPhilip\ElasticLens\Tests\Models\User;

it('resolves index model class from base model instance', function () {
    $user = new User;
    $indexClass = Lens::fetchIndexModelClass($user);

    expect($indexClass)->toBe(IndexedUser::class);
});

it('resolves index model class from base model string', function () {
    $indexClass = Lens::fetchIndexModelClass(User::class);

    expect($indexClass)->toBe(IndexedUser::class);
});

it('checks watcher status', function () {
    // No watchers configured in test
    expect(Lens::checkIfWatched(User::class, IndexedUser::class))->toBeFalse();

    // Configure a watcher
    config()->set('elasticlens.watchers', [
        User::class => [IndexedUser::class],
    ]);

    expect(Lens::checkIfWatched(User::class, IndexedUser::class))->toBeTrue();
});
