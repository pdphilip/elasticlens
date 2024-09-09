<?php
$name = $name = \Illuminate\Support\Str::plural($model);

$title = 'Building '.$name.' ';
$state = 'running';
if ($completed) {
    $state = 'success';
}
?>
<div>
    @include('elasticlens::cli.components.loader-spin',['message' => $title,'i' => $i,'state' => $state])
    @include('elasticlens::cli.components.hr')
    @include('elasticlens::cli.components.data-row-value',['key' => 'Created','value' => $created,'class' => 'text-sky-500'])
    @include('elasticlens::cli.components.data-row-value',['key' => 'Updated','value' => $updated,'class' => 'text-emerald-500'])
    @include('elasticlens::cli.components.data-row-value',['key' => 'Failed','value' => $failed,'class' => 'text-rose-500'])
</div>