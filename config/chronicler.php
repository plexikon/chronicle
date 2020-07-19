<?php

return [

    'event' => [
        //  need another contract
        //'alias' => \Plexikon\Chronicle\Messaging\Alias\InflectorMessageAlias::class,
        'serializer' => \Plexikon\Chronicle\Messaging\Serializer\EventSerializer::class,
        'decorators' => [
            \Plexikon\Chronicle\Messaging\Decorator\AggregateIdTypeMessageDecorator::class,
        ]
    ],

    'models' => [
        'event_stream' => \Plexikon\Chronicle\Chronicling\Model\EventStream::class,
        'projection' => \Plexikon\Chronicle\Chronicling\Model\Projection::class
    ],

    'connections' => [

        'pgsql' => [
            'driver' => 'pgsql',
            'strategy' => \Plexikon\Chronicle\Chronicling\Persistence\PgsqlSingleStreamStrategy::class,
            'tracker_id' => null,
            'options' => [
                'disable_transaction' => false,
                'use_write_lock' => true,
                'use_event_decorator' => true,
                'use_event_transaction' => true,
            ],
        ],

        'in_memory' => []
    ],

    'repositories' => [

    ],

    'console' => [
        'load_migrations' => true,
        'load_commands' => true,
        'commands' => [
            \Plexikon\Chronicle\Support\Console\CreateEventStreamCommand::class,
        ]
    ]
];
