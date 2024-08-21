<div>
    @if(!empty($indexes))
        @foreach ($indexes as $index)
            @include('elasticlens::cli.partials.status',['title' => $index['name'],'status' => $index['indexStatus']['status'],'name' => $index['indexStatus']['name'],'help' => $index['indexStatus']['help'] ?? null])
            
            @foreach ($index['checks'] as $check)
                @include('elasticlens::cli.partials.data-row',['name' => $check['label'],'status' => $check['status'],'extra' => $check['extra'] ?? null,'help' => $check['help'] ?? null])
            @endforeach
            @include('elasticlens::cli.partials.new-line',['count' => 2])
        @endforeach
    @else
        @include('elasticlens::cli.partials.error',['message' => 'No indexes found'])
    @endif
</div>
