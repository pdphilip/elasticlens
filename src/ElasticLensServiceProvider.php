<?php

namespace PDPhilip\ElasticLens;

use PDPhilip\ElasticLens\Commands\LensBuildCommand;
use PDPhilip\ElasticLens\Commands\LensHealthCommand;
use PDPhilip\ElasticLens\Commands\LensMakeCommand;
use PDPhilip\ElasticLens\Commands\LensStatusCommand;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ElasticLensServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('elasticlens')
            ->hasConfigFile()
            ->hasViews('elasticlens')
            ->hasMigration('create_indexable_build_states_index')
            ->runsMigrations()
            ->hasCommand(LensHealthCommand::class)
            ->hasCommand(LensStatusCommand::class)
            ->hasCommand(LensBuildCommand::class)
            ->hasCommand(LensMakeCommand::class)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->setName('lens:install')
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->copyAndRegisterServiceProviderInApp()
                    ->askToStarRepoOnGitHub('pdphilip/elasticlens');
            });
    }
}
