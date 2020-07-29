<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling\Aggregate;

use Plexikon\Chronicle\Chronicling\Aggregate\Concerns\HasReconstituteAggregate;
use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\DomainEvent;
use Plexikon\Chronicle\Stream\Stream;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateCache;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateRepository as BaseAggregateRepository;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateRoot;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\ReadOnlyChronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageDecorator;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;

final class AggregateRepository implements BaseAggregateRepository
{
    use HasReconstituteAggregate;

    protected string $aggregateRoot;
    protected Chronicler $chronicler;
    protected StreamName $streamName;
    protected AggregateCache $aggregateCache;
    protected MessageDecorator $messageDecorator;

    public function __construct(string $aggregateRoot,
                                Chronicler $chronicler,
                                AggregateCache $aggregateCache,
                                StreamName $streamName,
                                MessageDecorator $messageDecorator)
    {
        $this->aggregateRoot = $aggregateRoot;
        $this->chronicler = $chronicler;
        $this->aggregateCache = $aggregateCache;
        $this->streamName = $streamName;
        $this->messageDecorator = $messageDecorator;
    }

    public function retrieve(AggregateId $aggregateId): AggregateRoot
    {
        if ($this->aggregateCache->has($aggregateId)) {
            return $this->aggregateCache->get($aggregateId);
        }

        $aggregateRoot = $this->reconstituteAggregateRoot($aggregateId);

        if ($aggregateRoot->exists()) {
            $this->aggregateCache->put($aggregateRoot);
        }

        return $aggregateRoot;
    }

    public function persist(AggregateRoot $aggregateRoot): void
    {
        $this->persistEvents(
            $aggregateRoot->aggregateId(),
            $aggregateRoot->version(),
            ...$aggregateRoot->releaseEvents()
        );
    }

    public function persistEvents(AggregateId $aggregateId, int $aggregateVersion, DomainEvent ...$events): void
    {
        $aggregateVersion = $aggregateVersion - count($events);

        Assertion::greaterOrEqualThan($aggregateVersion, 0, 'Invalid aggregate version');

        $headers = [
            MessageHeader::AGGREGATE_ID => $aggregateId,
            MessageHeader::AGGREGATE_TYPE => $this->aggregateRoot
        ];

        $messages = array_map(
            function (DomainEvent $event) use ($headers, &$aggregateVersion) {
                return $this->messageDecorator->decorate(
                    new Message(
                        $event,
                        $headers + [MessageHeader::AGGREGATE_VERSION => ++$aggregateVersion]
                    ));
            }, $events);

        $this->chronicler->persist(new Stream($this->streamName, $messages));

        $this->aggregateCache->forget($aggregateId);
    }

    public function chronicler(): ReadOnlyChronicler
    {
        return $this->chronicler;
    }

    public function flushCache(): void
    {
        $this->aggregateCache->flush();
    }
}
