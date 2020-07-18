<?php

return [

    'clock' => \Plexikon\Chronicle\Clock\SystemClock::class,

    'tracking' => [
        'tracker' => [
            'default' => [
                'abstract' => 'reporter.tracker.default',
                'concrete' => \Plexikon\Chronicle\Support\Contract\Reporter\TrackingReporter::class
            ],
        ],

        'subscribers' => [
            \Plexikon\Chronicle\Reporter\Subscriber\MessageFactorySubscriber::class,
        ],
    ],

    'messaging' => [

        'factory' => \Plexikon\Chronicle\Messaging\MessageFactory::class,
        'serializer' => \Plexikon\Chronicle\Messaging\Serializer\MessageSerializer::class,
        'payload_serializer' => \Plexikon\Chronicle\Messaging\Serializer\PayloadSerializer::class,
        'alias' => \Plexikon\Chronicle\Messaging\Alias\InflectorMessageAlias::class,
        'decorators' => [
            'commons' => [
                \Plexikon\Chronicle\Messaging\Decorator\EventIdMessageDecorator::class,
                \Plexikon\Chronicle\Messaging\Decorator\EventTypeMessageDecorator::class,
                \Plexikon\Chronicle\Messaging\Decorator\TimeOfRecordingMessageDecorator::class,
            ],

            'extra' => [

            ]
        ],

        'producer' => [

            'default' => 'sync',

            'per_message' => [
                'queue' => null,
                'connection' => null
            ],
            'async_all' => [
                'queue' => null,
                'connection' => null
            ],
        ]
    ],

    'reporting' => [
        'command' => [
            'default' => [
                'name' => \Plexikon\Chronicle\Reporter\ReportCommand::class, // null or your reporter id, default to concrete
                'concrete' => \Plexikon\Chronicle\Reporter\ReportCommand::class,
                //'tracker_id' => 'reporter.tracker.default',
                'route_strategy' => 'per_message',
                'handler_method' => 'command',
                'message' => [
                    'decorators' => [
                        \Plexikon\Chronicle\Messaging\Decorator\AsyncMarkerMessageDecorator::class,
                    ]
                ],
                'subscribers' => [
                    \Plexikon\Chronicle\Reporter\Subscriber\TrackingCommandSubscriber::class,
                    \Plexikon\Chronicle\Reporter\Subscriber\CommandValidationSubscriber::class,
                    \Plexikon\Chronicle\Reporter\Subscriber\LoggerCommandSubscriber::class,
                ],
                'map' => []
            ]
        ],

        'event' => [
            'default' => [
                'name' => \Plexikon\Chronicle\Reporter\ReportEvent::class, // null or your reporter id, default to concrete
                'concrete' => \Plexikon\Chronicle\Reporter\ReportEvent::class,
                'route_strategy' => 'sync',
                'message' => [
                    'decorators' => [
                        \Plexikon\Chronicle\Messaging\Decorator\AsyncMarkerMessageDecorator::class,
                    ]
                ],
                'handler_method' => 'onEvent',
                //'allow_no_message_handler' => true,
                'subscribers' => [
                    \Plexikon\Chronicle\Reporter\Subscriber\TrackingEventSubscriber::class,
                ],
                'map' => []
            ]
        ],

        'query' => [
            'default' => [
                'name' => \Plexikon\Chronicle\Reporter\ReportQuery::class, // null or your reporter id, default to concrete
                'concrete' => \Plexikon\Chronicle\Reporter\ReportQuery::class,
                'route_strategy' => 'sync',
                'message' => [
                    'decorators' => []
                ],
                'handler_method' => 'query',
                'subscribers' => [
                    \Plexikon\Chronicle\Reporter\Subscriber\TrackingQuerySubscriber::class,
                ],
                'map' => []
            ]
        ]
    ],
];
