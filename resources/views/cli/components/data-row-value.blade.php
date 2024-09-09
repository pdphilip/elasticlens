<?php

$name = \Illuminate\Support\Str::headline($key);
if (! empty($skipTitle)) {
    $name = $key;
}

if ($value === true) {
    $class = 'text-emerald-500';
    $value = 'Yes';
}
if ($value === false) {
    $class = 'text-rose-500';
    $value = 'No';
}

if ($value === 0) {
    $class = 'text-stone-600';
} elseif (is_int($value)) {
    $value = number_format($value);
}
if (empty($class)) {
    $class = '';
}
?>
<div>
    <div class="flex space-x-1 px-1">
        <span class="font-bold">{{ $name }}</span>
        <span class="flex-1 content-repeat-[.] text-gray"></span>
        <span class="text-right">
        <span class="{{$class}} font-bold  px-1 ">{{$value}}</span>
        </span>
    </div>
</div>
