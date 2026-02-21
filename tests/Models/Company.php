<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PDPhilip\ElasticLens\Indexable;

class Company extends Model
{
    use Indexable;

    protected $connection = 'sqlite';

    protected static $unguarded = true;

    public static function executeSchema(): void
    {
        $schema = Schema::connection('sqlite');
        $schema->dropIfExists('companies');
        $schema->create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('industry')->nullable();
            $table->timestamps();
        });
    }
}
