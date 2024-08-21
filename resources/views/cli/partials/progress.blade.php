<?php
$length = $screenWidth - (30);
$progress = floor($current / $max * $length);
$remaining = $length - $progress;
$percentage = round(($current / $max) * 100);
$progressColor = "bg-sky-600 text-sky-500";
if ($max == $current) {
    $progressColor = "bg-emerald-600 text-emerald-300";
}
?>
<div class="mx-1">
    <div class="flex w-{{$length + 12}}">
        <span class="w-10"></span><span>╭</span><span class="flex-1 content-repeat-[─]"></span><span>╮</span>
    </div>
    <div class="flex">
        <span class="w-9 text-right">{{$current}}/{{$max}}</span>
        <span class="w-1"></span>
        <span class="w-1">│</span>
        <span class="{{$progressColor}}  w-{{$progress}} content-repeat-[▁] text-right"></span>
        <span class="bg-slate-700 text-slate-500  w-{{$remaining}} content-repeat-[▁] text-right"></span>
        <span class="w-1">│</span>
        <span class="ml-2">{{$percentage}}%</span>
    </div>
    <div class="flex w-{{$length + 12}}">
        <span class="w-10"></span><span>╰</span><span class="flex-1 content-repeat-[─]"></span><span>╯</span>
    </div>
</div>