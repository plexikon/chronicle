<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling;

use Plexikon\Chronicle\Support\Contract\Tracker\Listener;

interface EventChronicler extends ChroniclerDecorator
{
    public const FIRST_COMMIT_EVENT = 'first_commit_stream_event';
    public const PERSIST_STREAM_EVENT = 'persist_stream_event';
    public const RETRIEVE_ALL_STREAM_EVENT = 'retrieve_all_stream_event';
    public const RETRIEVE_ALL_REVERSE_STREAM_EVENT = 'retrieve_all_reverse_stream_event';
    public const RETRIEVE_ALL_FILTERED_STREAM_EVENT = 'retrieve_all_filtered_stream_event';
    public const DELETE_STREAM_EVENT = 'delete_stream_event';
    public const HAS_STREAM_EVENT = 'has_stream_event';
    public const FETCH_STREAM_NAMES = 'fetch_stream_names_event';

    /**
     * @param string $eventName
     * @param callable $eventContext
     * @param int $priority
     * @return Listener
     */
    public function subscribe(string $eventName, callable $eventContext, int $priority = 0): Listener;

    /**
     * @param Listener ...$eventSubscribers
     */
    public function unsubscribe(Listener ...$eventSubscribers): void;
}
