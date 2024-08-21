<div class="m-1">
    @include('elasticlens::cli.partials.status',['title' => $health['indexStatus']['title'],'status' => $health['indexStatus']['status'],'name' => $health['indexStatus']['name'],'help' => $health['indexStatus']['help'] ?? null])
    @include('elasticlens::cli.partials.new-line')
    @include('elasticlens::cli.partials.header-row',['name' => 'Index Model','extra' => null,'value' => 'Value'])
    @foreach ($health['indexData'] as $detail => $value)
        @include('elasticlens::cli.partials.data-row-value',['key' => $detail,'value' => $value])
    @endforeach
    @include('elasticlens::cli.partials.new-line')
    @include('elasticlens::cli.partials.header-row',['name' => 'Base Model','extra' => null,'value' => 'Value'])
    @foreach ($health['modelData'] as $detail => $value)
        @include('elasticlens::cli.partials.data-row-value',['key' => $detail,'value' => $value])
    @endforeach
    @include('elasticlens::cli.partials.new-line')
    @include('elasticlens::cli.partials.header-row',['name' => 'Build Data','extra' => null,'value' => 'Value'])
    @foreach ($health['buildData'] as $detail => $value)
        @include('elasticlens::cli.partials.data-row-value',['key' => $detail,'value' => $value])
    @endforeach
</div>