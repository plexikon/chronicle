<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Messaging\Producer;

use Illuminate\Contracts\Bus\QueueingDispatcher;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\DetectReporterName;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageSerializer;

class IlluminateProducer
{
    use DetectReporterName;

    private QueueingDispatcher $queueingDispatcher;
    private MessageSerializer $messageSerializer;
    private ?string $connection;
    private ?string $queue;

    public function __construct(QueueingDispatcher $queueingDispatcher,
                                MessageSerializer $messageSerializer,
                                ?string $connection,
                                ?string $queue)
    {
        $this->queueingDispatcher = $queueingDispatcher;
        $this->messageSerializer = $messageSerializer;
        $this->connection = $connection;
        $this->queue = $queue;
    }

    public function handle(Message $message): void
    {
        $payload = $this->messageSerializer->serializeMessage($message);

        $messageJob = $this->toMessageJob($payload, $this->detectBusTypeFromMessage($message));

        $this->queueingDispatcher->dispatchToQueue($messageJob);
    }

    private function toMessageJob(array $payload, string $busType): MessageJob
    {
        return new MessageJob($payload, $busType, $this->connection, $this->queue);
    }
}
