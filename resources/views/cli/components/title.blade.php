<?php
$color = $color ?? 'sky';
?>
<div>
    @include('elasticlens::cli.components.title-row')
    @include('elasticlens::cli.components.title-row',['t' => $title])
    @include('elasticlens::cli.components.title-row')
</div>