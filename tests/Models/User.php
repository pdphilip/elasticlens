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

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function logs()
    {
        return $this->hasMany(UserLog::class);
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
            $table->integer('age')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
