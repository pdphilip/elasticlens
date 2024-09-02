<div>
    @include('elasticlens::cli.components.header-row',['name' => 'Config','extra' => 'Value','value' => 'Status'])
    @foreach ($checks as $check)
        @include('elasticlens::cli.components.data-row',['name' => $check['label'],'status' => $check['status'],'extra' => $check['extra'] ?? null,'help' => $check['help'] ?? null])
    @endforeach
</div>
