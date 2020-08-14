<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\Connection;

use Generator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\LazyCollection;
use Plexikon\Chronicle\Exception\QueryFailure;
use Plexikon\Chronicle\Exception\StreamNotFound;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Messaging\EventSerializer;
use Plexikon\Chronicle\Support\Json;
use stdClass;

class StreamEventLoader
{
    private EventSerializer $eventSerializer;

    public function __construct(EventSerializer $eventSerializer)
    {
        $this->eventSerializer = $eventSerializer;
    }

    /**
     * @param Builder $builder
     * @param StreamName $streamName
     * @return Generator<Message>
     */
    public function query(Builder $builder, StreamName $streamName): Generator
    {
        try {
            $events = $this->fromCursor($builder, $streamName);

            foreach ($events as $event) {
                yield from $this->unserializeEvent($event);
            }

            return $events->count();
        } catch (QueryException $queryException) {
            if ($queryException->getCode() === '42S22') {
                throw QueryFailure::fromQueryException($queryException);
            }

            if ($queryException->getCode() !== '00000') {
                throw StreamNotFound::withStreamName($streamName);
            }

            throw QueryFailure::fromQueryException($queryException);
        }
    }

    /**
     * @param Builder $builder
     * @param StreamName $streamName
     * @return LazyCollection<stdClass>
     */
    protected function fromCursor(Builder $builder, StreamName $streamName): LazyCollection
    {
        return $builder->cursor()->whenEmpty(static function () use ($streamName) {
            throw StreamNotFound::withStreamName($streamName);
        });
    }

    /**
     * @param stdClass $event
     * @return Generator<Message>
     */
    protected function unserializeEvent(stdClass $event): Generator
    {
        $data = [
            'payload' => Json::decode($event->payload),
            'headers' => Json::decode($event->headers),
            'no' => $event->no
        ];

        yield from $this->eventSerializer->unserializePayload($data);
    }
}
