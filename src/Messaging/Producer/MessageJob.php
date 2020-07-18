<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Messaging\Producer;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Queue;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;

final class MessageJob
{
    public ?string $connection;
    private ?string $queue;
    private array $payload;
    private string $busType;

    public function __construct(array $payload, string $busType, ?string $connection, ?string $queue)
    {
        $this->payload = $payload;
        $this->busType = $busType;
        $this->connection = $connection;
        $this->queue = $queue;
    }

    /**
     * @param Container $container
     */
    public function handle(Container $container): void
    {
        /** @var Reporter $serviceBus */
        $serviceBus = $container->get($this->busType);

        $serviceBus->publish($this->payload);
    }

    /**
     * @param Queue $queue
     * @param MessageJob $messageJob
     * @internal
     */
    public function queue(Queue $queue, MessageJob $messageJob): void
    {
        $queue->pushOn($this->queue, $messageJob);
    }

    public function displayName(): string
    {
        return $this->payload['headers'][MessageHeader::EVENT_TYPE];
    }
}
