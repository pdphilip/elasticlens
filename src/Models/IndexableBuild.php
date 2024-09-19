<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Models;

use Illuminate\Support\Carbon;
use PDPhilip\ElasticLens\Enums\IndexableBuildState;
use PDPhilip\ElasticLens\Index\BuildResult;
use PDPhilip\Elasticsearch\Eloquent\Model;
use PDPhilip\Elasticsearch\Schema\Schema;

/**
 * App\Models\IndexableLog
 *
 ******Fields*******
 *
 * @property string $_id
 * @property string $model
 * @property string $model_id
 * @property string $index_model
 * @property string $last_source
 * @property IndexableBuildState $state
 * @property array $state_data
 * @property array $logs
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 ******Attributes*******
 * @property-read string $state_name
 * @property-read string $state_color
 *
 * @mixin Model
 */
class IndexableBuild extends Model
{
    public $connection = 'elasticsearch';

    protected $appends = [
        'state_name',
        'state_color',
    ];

    protected $casts = [
        'state' => IndexableBuildState::class,
    ];

    public function __construct()
    {
        parent::__construct();
        $this->setConnection(self::getConnectionName());
    }

    public static function isEnabled()
    {
        return config('elasticlens.index_build_state.enabled', true);
    }

    public function getStateNameAttribute(): string
    {
        return $this->state->label();
    }

    public function getStateColorAttribute(): string
    {
        return $this->state->color();
    }

    public static function returnState($model, $modelId, $indexModel): mixed
    {
        return self::where('model', strtolower($model))->where('model_id', $modelId)->where('index_model', strtolower($indexModel))->first();
    }

    public static function writeState($model, $modelId, $indexModel, BuildResult $buildResult, $observerModel): ?IndexableBuild
    {
        if (! self::isEnabled()) {
            return null;
        }
        $model = strtolower($model);
        $indexModel = strtolower($indexModel);
        $stateData = $buildResult->toArray();
        unset($stateData['model']);
        $state = IndexableBuildState::FAILED;
        if ($buildResult->success) {
            $state = IndexableBuildState::SUCCESS;
            unset($stateData['msg']);
            unset($stateData['details']);
            unset($stateData['map']);
        }

        $source = $observerModel;
        $stateModel = self::returnState($model, $modelId, $indexModel);
        if (! $stateModel) {
            $stateModel = new IndexableBuild;
            $stateModel->model = $model;
            $stateModel->model_id = $modelId;
            $stateModel->index_model = $indexModel;
        }
        $stateModel->state = $state;
        $stateModel->state_data = $stateData;
        $stateModel->last_source = $source;
        $logs = $stateModel->_prepLogs($stateData, $source);
        $stateModel->logs = $logs;
        $stateModel->saveWithoutRefresh();

        return $stateModel;
    }

    public static function countModelErrors($indexModel): int
    {
        return IndexableBuild::where('index_model', strtolower($indexModel))->where('state', IndexableBuildState::FAILED)->count();
    }

    public static function countModelRecords($indexModel): int
    {
        return IndexableBuild::where('index_model', strtolower($indexModel))->count();
    }

    public static function deleteState($model, $modelId, $indexModel): void
    {
        $stateModel = IndexableBuild::returnState(strtolower($model), $modelId, strtolower($indexModel));
        $stateModel?->delete();
    }

    public static function deleteStateModel($indexModel): void
    {
        IndexableBuild::where('index_model', strtolower($indexModel))->delete();

    }

    //----------------------------------------------------------------------
    // Helpers
    //----------------------------------------------------------------------

    public function _prepLogs($stateData, $source): array
    {
        $trim = config('elasticlens.index_build_state.log_trim', 2);
        if (! $trim) {
            return [];
        }
        $logs = $this->logs ?? [];
        unset($stateData['_id']);
        $logs[] = [
            'ts' => time(),
            'success' => $stateData['success'],
            'data' => $stateData,
            'source' => $source,
        ];
        $collection = collect($logs);

        return $collection->sortByDesc('ts')->take($trim)->values()->all();

    }

    //----------------------------------------------------------------------
    // Config
    //----------------------------------------------------------------------

    public static function connectionName(): string
    {
        return config('elasticlens.database', 'elasticsearch');
    }

    public static function checkHasIndex(): bool
    {
        $connectionName = self::connectionName();

        return Schema::on($connectionName)->hasIndex('indexable_builds');
    }
}
