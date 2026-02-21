<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PDPhilip\ElasticLens\Indexable;

class User extends Model
{
    use Indexable;
    use SoftDeletes;

    protected $connection = 'sqlite';

    protected static $unguarded = true;

    public static ?\Closure $excludeIndexUsing = null;

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function logs()
    {
        return $this->hasMany(UserLog::class);
    }

    public function excludeIndex(): bool
    {
        if (static::$excludeIndexUsing) {
            return (bool) (static::$excludeIndexUsing)($this);
        }

        return false;
    }

    public static function executeSchema(): void
    {
        $schema = Schema::connection('sqlite');
        $schema->dropIfExists('users');
        $schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('status')->default('active');
            $table->boolean('is_admin')->default(false);
            $table->integer('age')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
