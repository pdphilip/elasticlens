<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use OmniTerm\HasOmniTerm;
use PDPhilip\ElasticLens\Commands\Scripts\QualifyModel;
use PDPhilip\ElasticLens\Lens;
use PDPhilip\ElasticLens\Models\IndexableBuild;

class LensErrorsCommand extends Command
{
    use HasOmniTerm;

    public $signature = 'lens:errors {model : Base Model Name, example: User} {--per-page=10 : Errors per page}';

    public $description = 'Show build errors for the specified model index';

    public function handle(): int
    {
        $model = $this->argument('model');
        $modelCheck = QualifyModel::check($model);
        if (! $modelCheck['qualified']) {
            $this->omni->statusError('ERROR', 'Model not found', ['Model: '.$model]);

            return self::FAILURE;
        }

        $model = $modelCheck['qualified'];
        $indexModel = Lens::fetchIndexModelClass($model);

        $this->newLine();
        $this->omni->titleBar('Build Errors: '.$model, 'rose');
        $this->newLine();

        if (! IndexableBuild::checkHasIndex()) {
            $this->omni->statusWarning('NO INDEX', 'The indexable_builds index does not exist', [
                'Run lens:migrate to create it',
            ]);

            return self::FAILURE;
        }

        $errorCount = IndexableBuild::countModelErrors($indexModel);

        if ($errorCount === 0) {
            $this->omni->statusSuccess('ALL CLEAR', 'No build errors found');
            $this->newLine();

            return self::SUCCESS;
        }

        $perPage = (int) $this->option('per-page');
        $this->omni->statusError($errorCount.' ERROR'.($errorCount > 1 ? 'S' : ''), 'Failed index builds');
        $this->newLine();

        $this->displayPages($indexModel, $errorCount, $perPage);

        return self::SUCCESS;
    }

    private function displayPages(string $indexModel, int $errorCount, int $perPage): void
    {
        $offset = 0;

        while ($offset < $errorCount) {
            $errors = IndexableBuild::buildErrorsQuery($indexModel)
                ->orderByDesc('updated_at')
                ->offset($offset)
                ->limit($perPage)
                ->get();

            if ($errors->isEmpty()) {
                break;
            }

            $showing = $offset + $errors->count();
            $this->omni->info('Showing '.($offset + 1).'-'.$showing.' of '.$errorCount);
            $this->newLine();

            foreach ($errors as $error) {
                $this->omni->dataList($this->formatError($error), $error->model_id, 'text-rose-400');
            }

            $offset += $perPage;

            if ($offset >= $errorCount) {
                break;
            }

            $this->newLine();
            $nextEnd = min($offset + $perPage, $errorCount);
            $answer = null;
            while (! in_array($answer, ['yes', 'no', 'y', 'n'])) {
                $answer = $this->omni->ask('View '.($offset + 1).'-'.$nextEnd.' of '.$errorCount.'?', ['yes', 'no']);
            }

            if (in_array($answer, ['no', 'n'])) {
                break;
            }

            $this->newLine();
        }
    }

    private function formatError(IndexableBuild $error): array
    {
        $stateData = $error->state_data ?? [];
        $details = $this->extractDetails($stateData);

        return array_filter([
            'error' => $stateData['msg'] ?? 'Unknown error',
            'details' => $details,
            'source' => $error->last_source ?? null,
            'when' => $this->formatTimestamp($error->updated_at),
        ]);
    }

    private function extractDetails(array $stateData): ?string
    {
        $raw = $stateData['details'] ?? null;
        if (! $raw) {
            return null;
        }

        // Details often contain multi-line structured output like
        // "Bulk Insert Errors (Showing 1 of 1):\n698...: [exception] message"
        // Extract the actual exception message after the colon-newline header
        $lines = preg_split('/\r?\n/', trim($raw));
        $meaningful = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // Strip the model ID prefix (hex string followed by colon)
            $cleaned = preg_replace('/^[a-f0-9]{20,}:\s*/', '', $line);
            $meaningful[] = $cleaned;
        }

        return implode(' | ', $meaningful) ?: $raw;
    }

    private function formatTimestamp(mixed $timestamp): string
    {
        if (! $timestamp) {
            return '-';
        }

        $carbon = $timestamp instanceof Carbon ? $timestamp : Carbon::parse($timestamp);

        return $carbon->diffForHumans();
    }
}
