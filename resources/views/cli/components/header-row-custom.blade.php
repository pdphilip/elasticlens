<?php

$extraText = null;
if (! empty($extra)) {
    $extraText = $extra;
}

?>
<div>
    <div class="flex space-x-1 px-1">
        {!! $html !!}
        <span class="flex-1  text-gray"></span>
        @if($extraText)
            <span class="text-stone-400">{{ $extraText }}</span>
            <span class="text-gray font-bold  text-right ">/</span>
        @endif
        <span class="text-gray font-bold  text-right ">{{$value}}</span>
    </div>
    @include('elasticlens::cli.components.hr')
</div>