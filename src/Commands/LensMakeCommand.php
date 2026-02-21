<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Commands;

use Exception;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use OmniTerm\HasOmniTerm;
use ReflectionClass;
use RuntimeException;

class LensMakeCommand extends GeneratorCommand
{
    use HasOmniTerm;

    public $signature = 'lens:make {model}';

    public $description = 'Make a new index for the specified model';

    public function handle(): int
    {
        $this->newLine();
        $model = $this->argument('model');
        // ensure casing is correct
        $model = Str::studly($model);

        // Check if model exists
        $modelFound = null;
        $indexedModel = null;
        $namespaces = config('elasticlens.namespaces');
        $paths = config('elasticlens.index_paths');

        $config = [
            'model' => [
                'name' => '',
                'namespace' => '',
                'full' => '',
            ],
            'index' => [
                'name' => '',
                'namespace' => '',
                'full' => '',
                'path' => '',
            ],

        ];

        $notFound = [];
        foreach ($namespaces as $modelNamespace => $indexNameSpace) {
            $modelCheck = $modelNamespace.'\\'.$model;
            if ($this->class_exists_case_sensitive($modelCheck)) {
                $modelFound = $modelCheck;
                $config['model']['name'] = $model;
                $config['model']['namespace'] = $modelNamespace;
                $config['model']['full'] = $modelCheck;
                $config['index']['name'] = 'Indexed'.$model;
                $config['index']['namespace'] = $indexNameSpace;
                $config['index']['full'] = $indexNameSpace.'\\'.$config['index']['name'];

                $path = array_search($indexNameSpace, $paths);
                if (! $path) {
                    $this->omni->statusError('ERROR', 'Path for namespace '.$indexNameSpace.' not found', [
                        'Namespace found: '.$indexNameSpace,
                        'Check config("elasticlens.index_paths") for the correct {path} => \''.$indexNameSpace.'\'',
                    ]);
                    $this->newLine();

                    return self::FAILURE;
                }
                $config['index']['path'] = $path;
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
        if ($this->class_exists_case_sensitive($config['index']['full'])) {
            $this->omni->statusError('ERROR', 'Indexed Model (for '.$model.' Model) already exists at: '.$config['index']['full']);

            return self::FAILURE;
        }

        $path = $config['index']['path'].$config['index']['name'];

        $finalPath = $this->getPath($path);
        // Make sure the directory exists
        $this->makeDirectory($finalPath);

        // Get the stub file contents
        $stub = $this->files->get($this->getStub());
        // Replace the stub variables
        $stub = $this->replaceNamespaceCustom($stub, $config['model']['namespace']);
        $stub = $this->replaceModel($stub, $config['model']['name']);

        // Write the file to disk
        $this->files->put($finalPath, $stub);

        $this->omni->statusSuccess('SUCCESS', 'Indexed Model (for '.$model.' Model) created at: '.$indexedModel);
        $this->omni->statusInfo('1', 'Add the Indexable trait to your <span class="text-sky-500">'.$model.'</span> model');
        $this->omni->render((string) view('elasticlens::cli.components.code-trait', ['model' => $model]));
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

    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->laravel['path.base'].'/'.str_replace('\\', '/', $name).'.php';
    }

    public function replaceNamespaceCustom($stub, $namespace): string
    {
        return str_replace('{{ namespace }}', $namespace, $stub);
    }

    public function replaceModel($stub, $name): string
    {
        return str_replace('{{ model }}', $name, $stub);
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
