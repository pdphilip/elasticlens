<?php

namespace PDPhilip\ElasticLens\Models;

use Illuminate\Support\Carbon;
use PDPhilip\Elasticsearch\Eloquent\Model;
use PDPhilip\Elasticsearch\Schema\Schema;
use PDPhilip\ElasticLens\Enums\IndexableStateType;
use PDPhilip\ElasticLens\Index\BuildResult;

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
 * @property IndexableStateType $state
 * @property array $state_data
 * @property array $logs
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 ******Attributes*******
 * @property-read string $state_name
 * @property-read string $state_color
 *
 */
class IndexableBuildState extends Model
{
    protected int $logTrim = 2;

    public $connection = 'elasticsearch';

    protected $appends = [
        'state_name',
        'state_color',
    ];

    protected $casts = [
        'state' => IndexableStateType::class,
    ];

    public function __construct()
    {
        parent::__construct();
        $this->setConnection(self::getConnectionName());
    }

    public function getStateNameAttribute(): string
    {
        return $this->state->label();
    }

    public function getStateColorAttribute(): string
    {
        return $this->state->color();
    }

    public static function returnState($model, $modelId, $indexModel): ?IndexableBuildState
    {
        return IndexableBuildState::where('model', $model)->where('model_id', $modelId)->where('index_model', $indexModel)->first();
    }

    public static function writeState($model, $modelId, $indexModel, BuildResult $buildResult, $observerModel): ?IndexableBuildState
    {
        if (empty(config('elasticlens.index_build_state.enabled'))) {
            return null;
        }
        $stateData = $buildResult->toArray();
        unset($stateData['model']);
        $state = IndexableStateType::FAILED;
        if ($buildResult->success) {
            $state = IndexableStateType::SUCCESS;
            unset($stateData['msg']);
            unset($stateData['details']);
            unset($stateData['map']);
        }

        $source = $observerModel;
        $stateModel = self::returnState($model, $modelId, $indexModel);
        if (! $stateModel) {
            $stateModel = new IndexableBuildState;
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
        return IndexableBuildState::where('index_model', $indexModel)->where('state', IndexableStateType::FAILED)->count();
    }

    public static function countModelRecords($indexModel): int
    {
        return IndexableBuildState::where('index_model', $indexModel)->count();
    }

    public static function deleteState($model, $modelId, $indexModel): void
    {
        $stateModel = IndexableBuildState::returnState($model, $modelId, $indexModel);
        $stateModel?->delete();
    }

    public static function deleteStateModel($indexModel): void
    {
        IndexableBuildState::where('index_model', $indexModel)->delete();

    }

    //----------------------------------------------------------------------
    // Helpers
    //----------------------------------------------------------------------

    public function _prepLogs($stateData, $source): array
    {
        $trim = config('elasticlens.index_build_state.log_trim') ?? $this->logTrim;
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
        return config('elasticlens.database') ?? 'elasticsearch';
    }

    public static function checkHasIndex(): bool
    {
        $connectionName = self::connectionName();

        return Schema::on($connectionName)->hasIndex('indexable_build_states');
    }
}
