<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Models;

use Exception;
use Illuminate\Support\Carbon;
use PDPhilip\ElasticLens\Enums\IndexableMigrationLogState;
use PDPhilip\Elasticsearch\Eloquent\Model;
use PDPhilip\Elasticsearch\Schema\Schema;

/**
 * App\Models\IndexableLog
 *
 ******Fields*******
 *
 * @property string $id
 * @property string $index_model
 * @property IndexableMigrationLogState $state
 * @property array $map
 * @property int $version_major
 * @property int $version_minor
 * @property Carbon|null $created_at
 *
 ******Attributes*******
 * @property-read string $version
 * @property-read string $state_name
 * @property-read string $state_color
 */
class IndexableMigrationLog extends Model
{
    public $connection = 'elasticsearch';

    const UPDATED_AT = null;

    protected $casts = [
        'state' => IndexableMigrationLogState::class,
    ];

    public static function isEnabled()
    {
        return config('elasticlens.index_migration_logs.enabled', true);
    }

    public function __construct()
    {
        parent::__construct();
        $this->setConnection(self::getConnectionName());
    }

    public function getVersionAttribute()
    {
        return 'v'.$this->version_major.'.'.$this->version_minor;
    }

    public static function getLatestVersion($indexModel): ?string
    {
        $indexModel = strtolower($indexModel);
        if (! self::isEnabled()) {
            return null;
        }
        $latest = self::getLatestMigration($indexModel);

        return $latest?->version;
    }

    public static function getLatestMigration($indexModel): mixed
    {
        $indexModel = strtolower($indexModel);
        $log = null;
        try {
            $log = self::where('index_model', $indexModel)->orderBy('version_major', 'desc')->orderBy('version_minor', 'desc')->first();
        } catch (Exception $e) {

        }

        return $log;
    }

    public static function saveMigrationLog($indexModel, $majorVersion, $state, $map)
    {
        $indexModel = strtolower($indexModel);

        $minor = self::calculateNextMinorVersion($indexModel, $majorVersion);
        $log = new self;
        $log->index_model = $indexModel;
        $log->version_major = $majorVersion;
        $log->version_minor = $minor;
        $log->state = $state;
        $log->map = $map;
        $log->save();
    }

    public static function calculateNextMinorVersion($indexModel, $majorVersion): int
    {
        $indexModel = strtolower($indexModel);
        $lastMigration = self::getLatestMigration($indexModel);
        if ($lastMigration) {
            if ($lastMigration->version_major == $majorVersion) {
                return $lastMigration->version_minor + 1;
            }
        }

        return 0;
    }

    // ----------------------------------------------------------------------
    // Config
    // ----------------------------------------------------------------------

    public static function connectionName(): string
    {
        return config('elasticlens.database', 'elasticsearch');
    }

    public static function checkHasIndex(): bool
    {
        $connectionName = self::connectionName();

        return Schema::on($connectionName)->hasIndex('indexable_migration_logs');
    }
}
