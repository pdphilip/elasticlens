<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Commands;

use Exception;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use OmniTerm\OmniTerm;
use ReflectionClass;
use RuntimeException;

use function OmniTerm\render;

class LensMakeCommand extends GeneratorCommand
{
    use OmniTerm;

    public $signature = 'lens:make {model}';

    public $description = 'Make a new index for the specified model';

    public function handle(): int
    {
        $this->initOmni();

        $this->newLine();
        $model = $this->argument('model');
        //ensure casing is correct
        $model = Str::studly($model);

        //Check if model exists
        $modelFound = null;
        $indexedModel = null;
        $namespaces = config('elasticlens.namespaces');
        $notFound = [];
        foreach ($namespaces as $modelNamespace => $indexNameSpace) {
            $modelCheck = $modelNamespace.'\\'.$model;
            if ($this->class_exists_case_sensitive($modelCheck)) {
                $modelFound = $modelCheck;
                $indexedModel = $indexNameSpace.'\\Indexed'.$model;
                break;
            } else {
                $notFound[] = $modelCheck;
            }
        }
        if (! $modelFound) {
            foreach ($notFound as $modelCheck) {
                $this->omni->statusError('ERROR', 'Base Model ('.$model.') was not found at: '.$modelCheck);
                $this->newLine();
            }

            return self::FAILURE;
        }
        if ($this->class_exists_case_sensitive($indexedModel)) {
            $this->omni->statusError('ERROR', 'Indexed Model (for '.$model.' Model) already exists at: '.$indexedModel);

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

        $this->omni->statusSuccess('SUCCESS', 'Indexed Model (for '.$model.' Model) created at: '.$indexedModel);
        $this->omni->statusInfo('1', 'Add the Indexable trait to your <span class="text-sky-500">'.$model.'</span> model');
        render((string) view('elasticlens::cli.components.code-trait', ['model' => $model]));
        $this->newLine();
        $this->omni->statusInfo('2', 'Then run: "<span class="text-emerald-500">php artisan lens:build '.$model.'</span>" to index your model');

        return self::SUCCESS;
    }

    protected $type = 'Model';

    protected function getStub(): string
    {
        $stubPath = __DIR__.'/../../resources/stubs/IndexedBase.php.stub';

        if (! file_exists($stubPath)) {
            throw new RuntimeException('Stub file not found: '.$stubPath);
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
        } catch (Exception $e) {
            // Class doesn't exist or couldn't be autoloaded
            return false;
        }

    }
}
