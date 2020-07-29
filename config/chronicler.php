<?php

use Plexikon\Chronicle\Support\Contract\Projector\ProjectorOption;

return [

    'event' => [
        'serializer' => \Plexikon\Chronicle\Messaging\Serializer\EventSerializer::class,
        'decorators' => [
            \Plexikon\Chronicle\Messaging\Decorator\EventIdMessageDecorator::class,
            \Plexikon\Chronicle\Messaging\Decorator\DefaultHeadersDecorator::class,
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
            'scope' => \Plexikon\Chronicle\Support\QueryScope\PgsqlQueryScope::class
        ],

        'in_memory' => [
            'scope' => \Plexikon\Chronicle\Support\QueryScope\InMemoryQueryScope::class,
        ]
    ],

    'repositories' => [

        'stream_name' => [
            'aggregate_class_name' => 'fqcn',
            'chronicler_id' => 'service_id',
            'cache' => 10000, // 0 to disable aggregate caching
            'event_decorators' => [], // merge w/ event decorators
        ],
    ],

    'projectors' => [

        'use' => 'default',
        'projector' => [
            'default' => [
                'connection' => 'pgsql',
                'chronicler_id' => \Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler::class,
                'options' => 'lazy',
            ]
        ],

        'options' =>[
            'lazy' => [
                ProjectorOption::OPTION_PCNTL_DISPATCH => true,
                ProjectorOption::OPTION_LOCK_TIMEOUT_MS => 20000,
                ProjectorOption::OPTION_SLEEP => 10000,
                ProjectorOption::OPTION_UPDATE_LOCK_THRESHOLD => 15000,
                ProjectorOption::OPTION_PERSIST_BLOCK_SIZE => 1000,
            ],
        ]
    ],

    'console' => [
        'load_migrations' => true,
        'load_commands' => true,
        'commands' => [
            \Plexikon\Chronicle\Support\Console\CreateEventStreamCommand::class,
        ]
    ]
];
