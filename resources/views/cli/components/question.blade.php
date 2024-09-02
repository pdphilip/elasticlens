<?php

?>
<div class="mx-1">
    <div class="flex space-x-1">
        <span class="font-bold">{{$question}}</span>
        @if(!empty($options))
            <span>[<span class="text-emerald-500">{{implode('/',$options)}}</span>]</span>
        @endif
    </div>
    <div>❯</div>
</div>