<div class="m-1">
@include('elasticlens::cli.components.status',[
    'name' => '1',
    'title' => 'Add the Indexable trait to your <span class="text-sky-500">'.$model.'</span> model',
    'status' => 'info',
   
])
<code line="7" start-line="1" class="m-2">
namespace App\Models;

use PDPhilip\ElasticLens\Indexable;

class {{$model}} extends Model
{
    use Indexable;
</code>
@include('elasticlens::cli.components.status',[
    'name' => '2',
    'title' => 'Then run: "<span class="text-emerald-500">php artisan lens:build '.$model.'</span>" to index your model',
    'status' => 'info',
   
])
</div>