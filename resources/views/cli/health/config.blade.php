<div class="m-1">
    @include('elasticlens::cli.components.status',['title' => $health['configStatus']['title'],'status' => $health['configStatus']['status'],'name' => $health['configStatus']['name'],'help' => $health['configStatus']['help'] ?? null])
    @include('elasticlens::cli.components.new-line')
    @include('elasticlens::cli.components.header-row',['name' => 'Config','extra' => null,'value' => 'Value'])
    @foreach ($health['configData'] as $detail => $value)
        @include('elasticlens::cli.components.data-row-value',['key' => $detail,'value' => $value])
    @endforeach
    
    @if(!$health['observers'])
        @include('elasticlens::cli.components.new-line')
        @include('elasticlens::cli.components.warning',['message' => 'No observers found'])
    @else
        @include('elasticlens::cli.components.new-line')
        @include('elasticlens::cli.components.header-row',['name' => 'Observed Model','extra' => null,'value' => 'Type'])
        @foreach ($health['observers'] as $observer)
            @include('elasticlens::cli.components.data-row-value',['key' => $observer['key'],'value' => $observer['value'],'skipTitle' => true])
        @endforeach
    @endif
    @if($health['configStatusHelp']['critical'] || $health['configStatusHelp']['warning'])
        @include('elasticlens::cli.components.new-line')
        @include('elasticlens::cli.components.info',['message' => 'Config Help'])
        @if($health['configStatusHelp']['critical'])
            @foreach ($health['configStatusHelp']['critical'] as $critical)
                @include('elasticlens::cli.components.status',['title' => $critical['name'],'status' => 'error','name' => 'Config Error','help' => $critical['help'] ?? null])
                @include('elasticlens::cli.components.new-line')
            @endforeach
        
        @endif
        @if($health['configStatusHelp']['warning'])
            @foreach ($health['configStatusHelp']['warning'] as $warning)
                @include('elasticlens::cli.components.status',['title' => $warning['name'],'status' => 'warning','name' => 'Config Recommendation','help' => $warning['help'] ?? null])
                @include('elasticlens::cli.components.new-line')
            @endforeach
        
        @endif
    @endif
</div>