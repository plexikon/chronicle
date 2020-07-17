<?php

namespace Plexikon\Chronicle\Support\Contract\Messaging;

interface MessageHeader
{
    public const EVENT_ID = '__event_id';
    public const EVENT_TYPE = '__event_type';
    public const TIME_OF_RECORDING = '__time_of_recording';
    public const MESSAGE_BUS_TYPE = '__message_bus_type';
    public const MESSAGE_ASYNC_MARKED = '__message_async_marked';

    public const AGGREGATE_ID = '__aggregate_id';
    public const AGGREGATE_ID_TYPE = '__aggregate_id_type';
    public const AGGREGATE_VERSION = '__aggregate_version';
    public const AGGREGATE_TYPE = '__aggregate_type';
    public const INTERNAL_POSITION = '__internal_position';
    public const EVENT_CAUSATION_ID = '__event_causation_id';
    public const EVENT_CAUSATION_TYPE = '__event_causation_type';
}
