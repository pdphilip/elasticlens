<?php

return [
    'database' => 'elasticsearch',

    'queue' => null, // Set queue to use for dispatching index builds, ex: default, high, low, etc.

    'watchers' => [
        //        \App\Models\Profile::class => [
        //            \App\Models\Indexes\IndexedUser::class,
        //        ],
    ],

    'index_build_state' => [
        'enabled' => true, // Recommended to keep this enabled
        'log_trim' => 2, // If null, the logs field will be empty
    ],

    'index_migration_logs' => [
        'enabled' => true, // Recommended to keep this enabled
    ],
    'namespaces' => [
        'App\Models' => 'App\Models\Indexes',
    ],

    'index_paths' => [
        'app/Models/Indexes/' => 'App\Models\Indexes',
    ],

    'chunk_rates' => [
        'default' => 1000,
        'migrate_default' => 100,

        'relationship_scaling' => [
            'enabled' => true,
            'thresholds' => [
                3 => 750,
                6 => 500,
                9 => 250,
            ],
        ]
    ],
];
