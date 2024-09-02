<?php

switch ($status) {
    case 'disabled':
        $class = 'text-stone-500';
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
        <span class="{{ $class}} pr-1 font-bold">└────►</span>
        <span class="text-gray">{{$value}}</span>
    </div>
</div>