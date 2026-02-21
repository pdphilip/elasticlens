<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Profile extends Model
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
        $schema->dropIfExists('profiles');
        $schema->create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('bio')->nullable();
            $table->string('website')->nullable();
            $table->timestamps();
        });
    }
}
