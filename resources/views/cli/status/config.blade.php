<div>
    @include('elasticlens::cli.partials.header-row',['name' => 'Config','extra' => 'Value','value' => 'Status'])
    @foreach ($checks as $check)
        @include('elasticlens::cli.partials.data-row',['name' => $check['label'],'status' => $check['status'],'extra' => $check['extra'] ?? null,'help' => $check['help'] ?? null])
    @endforeach
</div>
