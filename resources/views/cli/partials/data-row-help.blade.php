<?php

switch ($status) {
    case 'disabled':
        $class = 'text-stone-600';
        break;
    case 'warning':
        $class = 'text-amber-500';
        break;
    case 'error':
        $class = 'text-rose-500';
        break;
    default:
        $class = 'text-gray';

}
?>
<div>
    <div class="flex space-x-1 px-1">
        <span class="flex-1  text-gray"></span>
        <span class="text-gray">{{$value}}</span>
        <span class="{{ $class}} pr-1 font-bold">◄────┘</span>
    </div>
</div>