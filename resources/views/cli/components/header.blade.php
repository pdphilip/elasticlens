<?php

$statusClass = $isSuccess ? 'bg-emerald-600 text-emerald-100 px-1' : 'bg-amber-700 text-amber-100 px-1';
if (!empty($critical)) {
    $statusClass = 'bg-rose-700 text-rose-100 px-1';
}


?>
@if(!empty($help))
    <div class="mx-1">
        <div class="flex space-x-1">
            <span class="flex-1 content-repeat-[-] text-gray"></span>
        </div>
        <div class="flex justify-between space-x-1">
            <span class="text-gray">|<span class="font-bold text-gray-50 px-1">{{$title}}</span></span>
            <span class="text-gray"><span class="{{$statusClass}}">{{$status}}</span><span class="text-amber-500 px-1">{{$help}}</span>|</span>
        </div>
        <div class="flex space-x-1">
            <span class="flex-1 content-repeat-[-] text-gray"></span>
        </div>
    </div>
@else
    <div class="mx-1">
        <div class="flex space-x-1">
            <span class="flex-1 content-repeat-[-] text-gray"></span>
        </div>
        <div class="flex justify-between space-x-1">
            <span class="text-gray">|<span class="font-bold text-gray-50 px-1">{{$title}}</span></span>
            <span class="text-gray"><span class="{{$statusClass}}">{{$status}}</span> |</span>
        </div>
        <div class="flex space-x-1">
            <span class="flex-1 content-repeat-[-] text-gray"></span>
        </div>
    </div>
@endif

