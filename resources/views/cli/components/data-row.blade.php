<?php

switch ($status) {
    case 'disabled':
        $statusValue = 'DISABLED';
        $class = 'bg-zinc-500 text-zinc-400';
        break;
    case 'warning':
        $statusValue = 'WARNING';
        $class = 'bg-amber-500 text-amber-200';
        break;
    case 'error':
        $statusValue = 'ERROR';
        $class = 'bg-rose-500 text-rose-200';
        break;
    case 'enabled':
        $statusValue = 'ENABLED';
        $class = 'text-emerald-500';
        break;
    default:
        $statusValue = 'OK';
        $class = 'text-emerald-500';

}
$extraText = null;
if (! empty($extra)) {
    $extraText = $extra;
}

?>
<div>
    <div class="flex space-x-1 px-1">
        <span class="font-bold">{{ $name }}</span>
        <span class="flex-1 content-repeat-[.] text-gray"></span>
        @if($extraText)
            <span class="text-stone-400">[{{ $extraText }}]</span>
        @endif
        <span class="text-right">
        <span class="{{$class}} font-bold  px-1 ">{{$statusValue}}</span>
        </span>
    </div>
    @if(!empty($help))
        @foreach ($help as $helperRow)
            @include('elasticlens::cli.components.data-row-help',['value' => $helperRow])
        @endforeach
    @endif
</div>
