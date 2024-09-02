<?php
if (empty($count)) {
    $count = 1;
}
?>
@for($i = 0; $i < $count; $i++)
    <div class="flex space-x-1 px-1">
        <span class="flex-1 content-repeat-[ ] "></span>
    </div>
@endfor
