<?php
$characters = ["⠉⠉", "⠈⠙", "⠀⠹", "⠀⢸", "⠀⣰", "⢀⣠", "⣀⣀", "⣄⡀", "⣆⠀", "⡇⠀", "⠏⠀", "⠋⠁"];
$colors = ["text-amber-500", "text-emerald-500", "text-rose-500", "text-sky-500"];
$intervals = count($characters) - 1;
$j = 0;
while ($i > $intervals) {
    $i -= $intervals;
    $j++;
    if ($j > 3) {
        $j = 0;
    }
}

$show = $characters[$i];
$textColor = $colors[$j];
switch ($state) {
    case 'success':
        $textColor = "text-emerald-500";
        $show = "✔";
        break;
    case 'warning':
        $textColor = "text-amber-500";
        $show = "⚠";
        break;
    case 'failover':
        $textColor = "text-amber-500";
        $show = "◴";
        break;
    case 'error':
        $textColor = "text-rose-500";
        $show = "✘";
        break;

}
?>
<div class="m-1 flex">
    <span class="{{$textColor}} mx-1">
        {{ $show }}
    </span>
    <span class="mx-1">{{$message}}</span>
    @if(!empty($details))
        <span class="mx-1 text-slate-600">{{$details}}</span>
    @endif
</div>