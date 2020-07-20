<?php

return [

    'event' => [
        //  need another contract
        //'alias' => \Plexikon\Chronicle\Messaging\Alias\InflectorMessageAlias::class,
        'serializer' => \Plexikon\Chronicle\Messaging\Serializer\EventSerializer::class,

        // commons messaging decorators from reporter will be merged
        'decorators' => [
            \Plexikon\Chronicle\Messaging\Decorator\AggregateIdTypeEventDecorator::class,
        ],
    ],

    'models' => [
        'event_stream' => \Plexikon\Chronicle\Chronicling\Model\EventStream::class,
        'projection' => \Plexikon\Chronicle\Chronicling\Model\Projection::class
    ],

    'connections' => [

        'pgsql' => [
            'driver' => 'pgsql',
            'strategy' => \Plexikon\Chronicle\Chronicling\Strategy\PgsqlSingleStreamStrategy::class,

            // tracker must fit options (could be checked inside manager)
            'tracking' => [
                'tracker_id' => 'service_id',
                'subscribers' => []
            ],
            'options' => [
                'disable_transaction' => false,
                'use_write_lock' => true,
                'use_event_decorator' => true
            ],
        ],

        'in_memory' => []
    ],

    'repositories' => [
        'stream_name' => [
            'aggregate_class_name' => 'fqcn',
            'chronicler_id' => 'service_id',
            'cache' => 10000, // 0 to disable aggregate caching
            'event_decorators' => [], // merge w/ event decorators
        ],
    ],

    'console' => [
        'load_migrations' => true,
        'load_commands' => true,
        'commands' => [
            \Plexikon\Chronicle\Support\Console\CreateEventStreamCommand::class,
        ]
    ]
];
