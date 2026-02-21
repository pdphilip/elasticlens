<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UserLog extends Model
{
    protected $connection = 'sqlite';

    protected static $unguarded = true;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function executeSchema(): void
    {
        $schema = Schema::connection('sqlite');
        $schema->dropIfExists('user_logs');
        $schema->create('user_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->string('details')->nullable();
            $table->timestamps();
        });
    }
}
