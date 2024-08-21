<?php
$color = $color ?? 'sky';
?>
<div>
    @include('elasticlens::cli.partials.title-row')
    @include('elasticlens::cli.partials.title-row',['t' => $title])
    @include('elasticlens::cli.partials.title-row')
</div>