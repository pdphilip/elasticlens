<?php
switch ($status) {
    case 'disabled':
        $class = 'bg-zinc-500 text-zinc-100';
        $color = 'text-zinc-500';
        break;
    case 'warning':
        $class = 'bg-amber-500 text-amber-200';
        $color = 'text-amber-500';
        break;
    case 'error':
        $class = 'bg-rose-500 text-rose-200';
        $color = 'text-rose-500';
        break;
    case 'info':
        $class = 'bg-sky-500 text-sky-200';
        $color = 'text-sky-500';
        break;
    case 'enabled':
        $class = 'bg-emerald-500 text-emerald-100';
        $color = 'text-emerald-500';
        break;
    default:
        $class = 'bg-emerald-500  text-emerald-100';
        $color = 'text-emerald-500';

}
$extraText = null;
if (! empty($extra)) {
    $extraText = $extra;
}

?>
<div>
    @include('elasticlens::cli.components.hr',['color' => $color])
    <div class="flex space-x-1 mx-1">
        <span class="{{$class}} px-1 ml-1">{{$name}}</span>
        <span class="flex-1">{!! $title !!}</span>
        @if(!empty($help))
            @foreach ($help as $helperRow)
                @include('elasticlens::cli.components.status-help',['value' => $helperRow])
            @endforeach
        @endif
    </div>
    @include('elasticlens::cli.components.hr',['color' => $color])
</div>