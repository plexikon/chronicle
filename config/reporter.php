<?php

return [

    'clock' => \Plexikon\Chronicle\Clock\SystemClock::class,

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

        'subscribers' => [
            \Plexikon\Chronicle\Reporter\Subscribers\MessageFactorySubscriber::class,
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

    /**
     * Reporter
     *
     * name: optional - service used to bind reporter in container (default to concrete class)
     * concrete: optional - fqcn class
     * route_strategy: sync, per_message, async_all
     *                 specify to override message.producer.default
     *                 query reporter is always sync
     * handler_method: optional - __invoke or specify a method name
     * messaging decorators: merged with messaging.decorators
     * messaging subscribers: merged with messaging.subscribers
     * tracker_id: ( not set ) specify a tracker service id i/o the default provided
     * allow_no_message_handler : only for event reporter
     */
    'reporting' => [

        'command' => [

            'default' => [
                'handler_method' => 'command',
                'messaging' => [
                    'decorators' => [
                        \Plexikon\Chronicle\Messaging\Decorator\AsyncMarkerMessageDecorator::class,
                    ],
                    'subscribers' => [
                        \Plexikon\Chronicle\Reporter\Subscribers\TrackingCommandSubscriber::class,
                        \Plexikon\Chronicle\Reporter\Subscribers\CommandValidationSubscriber::class,
                        \Plexikon\Chronicle\Reporter\Subscribers\LoggerCommandSubscriber::class,
                    ],
                ],

                'map' => []
            ]
        ],

        'event' => [

            'default' => [
                'handler_method' => 'onEvent',
                'messaging' => [
                    'decorators' => [
                        \Plexikon\Chronicle\Messaging\Decorator\AsyncMarkerMessageDecorator::class,
                    ],
                    'subscribers' => [
                        \Plexikon\Chronicle\Reporter\Subscribers\TrackingEventSubscriber::class,
                    ],
                ],
                'map' => []
            ]
        ],

        'query' => [

            'default' => [
                'handler_method' => 'query',
                'messaging' => [
                    'subscribers' => [
                        \Plexikon\Chronicle\Reporter\Subscribers\TrackingQuerySubscriber::class,
                    ],
                ],

                'map' => []
            ]
        ]
    ],
];
