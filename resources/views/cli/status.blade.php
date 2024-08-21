<div>
    @include('elasticlens::cli.partials.title',[
                    'title' => 'OmniLens Status',
                'color' => 'teal',
    ])
    @include('elasticlens::cli.partials.new-line')
    @include('elasticlens::cli.status.config',[
        'checks' => $checks,
    ])
    @include('elasticlens::cli.partials.new-line',['count' => 2])
    @include('elasticlens::cli.status.indexes',[
        'indexes' => $indexes,
    ])
</div>
