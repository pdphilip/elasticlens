<div>
    @include('elasticlens::cli.components.title',[
                    'title' => 'ElasticLens Status',
                'color' => 'teal',
    ])
    @include('elasticlens::cli.components.new-line')
    @include('elasticlens::cli.status.config',[
        'checks' => $checks,
    ])
    @include('elasticlens::cli.components.new-line',['count' => 2])
    @include('elasticlens::cli.status.indexes',[
        'indexes' => $indexes,
    ])
</div>
