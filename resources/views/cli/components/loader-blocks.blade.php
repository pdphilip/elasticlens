<?php
$colors = ['cyan', 'sky', 'blue', 'indigo', 'violet', 'indigo', 'blue', 'sky'];
$stepsPerColor = 20;
$i %= count($colors) * $stepsPerColor;
$colorIndex = floor($i / $stepsPerColor);
$nextColorIndex = ($colorIndex + 1) % count($colors);
$step = $i % $stepsPerColor;

$transitions = [
    [500, 500, 500, 500, 500], [400, 500, 500, 500, 500], [300, 400, 500, 500, 500],
    [300, 300, 400, 500, 500], [400, 300, 300, 400, 500], [500, 400, 300, 300, 400],
    [500, 500, 400, 300, 300], [500, 500, 500, 400, 300], [500, 500, 500, 500, 400],
    [500, 500, 500, 500, 500]
];

$colorTransitions = [
    [0, 0, 0, 0, 0], [0, 0, 0, 0, 0], [0, 0, 0, 0, 0],
    [1, 0, 0, 0, 0], [1, 1, 0, 0, 0], [1, 1, 1, 0, 0],
    [1, 1, 1, 1, 0], [1, 1, 1, 1, 1], [1, 1, 1, 1, 1],
    [1, 1, 1, 1, 1]
];

$blockWeights = $step < 10 ? $transitions[$step] : [500, 500, 500, 500, 500];
$blockColors = $step < 10 ? array_map(fn($v) => $v ? $nextColorIndex : $colorIndex, $colorTransitions[$step]) : array_fill(0, 5, $nextColorIndex);

$stateConfig = [
    'success' => ['text-emerald-500', '✔'],
    'warning' => ['text-amber-500', '⚠'],
    'failover' => ['text-amber-500', '◴'],
    'error' => ['text-rose-500', '✘']
];

[$textColor, $show] = $stateConfig[$state] ?? [null, false];
?>
<div class="m-1 flex">
    @if($show)
        <div class="{{$textColor}} mx-1">{{$show}}</div>
    @else
        <div class="mx-1 flex">
            @foreach($blockWeights as $index => $weight)
                <span class="w-1 bg-{{$colors[$blockColors[$index]]}}-{{$weight}}"></span>
            @endforeach
        </div>
    @endif
    <span class="mx-1">{{$message}}</span>
    @if(!empty($details))
        <span class="mx-1 text-slate-600">{{$details}}</span>
    @endif
</div>