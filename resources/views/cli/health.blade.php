<?php

?>
<div class="m-1">
    @include('elasticlens::cli.components.title',['title' => $health['title'],'color' => 'emerald'])
    @include('elasticlens::cli.health.index',['health' => $health])
    @include('elasticlens::cli.health.config',['health' => $health])
</div>
