<?php

namespace PDPhilip\ElasticLens\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;

use function Termwind\render;

class LensMakeCommand extends GeneratorCommand
{
    public $signature = 'lens:make {model}';

    public $description = 'Make a new index for the specified model';

    public function handle(): int
    {
        $this->newLine();
        $model = $this->argument('model');
        //ensure casing is correct
        $model = Str::studly($model);

        //Check if model exists
        $modelCheck = config('elasticlens.namespaces.models', 'App\Models').'\\'.$model;
        if (! $this->class_exists_case_sensitive($modelCheck)) {

            render(view('elasticlens::cli.components.status', [
                'name' => 'ERROR',
                'status' => 'error',
                'title' => 'Base Model ('.$model.') was not found at: '.$modelCheck,
            ]));

            $this->newLine();

            return self::FAILURE;

        }

        //check if there already is an indexedModel for the model
        $indexedModel = config('elasticlens.namespaces.indexes', 'App\Models\Indexes').'\\Indexed'.$model;
        if ($this->class_exists_case_sensitive($indexedModel)) {

            render(view('elasticlens::cli.components.status', [
                'name' => 'ERROR',
                'status' => 'error',
                'title' => 'Indexed Model (for '.$model.' Model) already exists at: '.$indexedModel,
            ]));

            $this->newLine();

            return self::FAILURE;
        }

        // Set the fully qualified class name for the new indexed model
        $name = $this->qualifyClass($indexedModel);

        // Get the destination path for the generated file
        $path = $this->getPath($name);

        // Make sure the directory exists
        $this->makeDirectory($path);

        // Get the stub file contents
        $stub = $this->files->get($this->getStub());

        // Replace the stub variables
        $stub = $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);

        // Write the file to disk
        $this->files->put($path, $stub);

        render(view('elasticlens::cli.components.status', [
            'name' => 'SUCCESS',
            'status' => 'success',
            'title' => 'Indexed Model (for '.$model.' Model) created at: '.$indexedModel,
        ]));
        render(view('elasticlens::cli.components.code-trait', [
            'model' => $model,
        ]));

        return self::SUCCESS;
    }

    protected $type = 'Model';

    protected function getDefaultNamespace($rootNamespace): string
    {
        return config('elasticlens.namespaces.indexes', $rootNamespace.'\\Models\Indexes');
    }

    protected function getStub(): string
    {
        $stubPath = __DIR__.'/../../resources/stubs/IndexedBase.php.stub';

        if (! file_exists($stubPath)) {
            throw new \RuntimeException('Stub file not found: '.$stubPath);
        }

        return $stubPath;
    }

    public function replaceClass($stub, $name): string
    {
        $stub = parent::replaceClass($stub, $name);

        return str_replace('{{ model }}', $this->argument('model'), $stub);
    }

    public function class_exists_case_sensitive(string $class_name): bool
    {
        if (in_array($class_name, get_declared_classes(), true)) {
            return true;
        }

        try {
            $reflectionClass = new ReflectionClass($class_name);

            return $reflectionClass->getName() === $class_name;
        } catch (ReflectionException $e) {
            // Class doesn't exist or couldn't be autoloaded
            return false;
        }

    }
}
