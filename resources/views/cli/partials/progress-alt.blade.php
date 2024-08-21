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
<div>
    <div class="flex">
        <span class="w-9 text-right">{{$current}}/{{$max}}</span> <span class="{{$progressColor}}  w-{{$current}}"></span>
    </div>
</div>

