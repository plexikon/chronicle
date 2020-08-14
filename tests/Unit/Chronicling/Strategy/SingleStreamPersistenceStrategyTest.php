<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Chronicling\Strategy;

use Plexikon\Chronicle\Chronicling\Strategy\SingleStreamPersistenceStrategy;
use Plexikon\Chronicle\Clock\SystemClock;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Messaging\EventSerializer;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Json;
use Plexikon\Chronicle\Tests\Double\SomeEvent;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Ramsey\Uuid\Uuid;

final class SingleStreamPersistenceStrategyTest extends TestCase
{
    private ObjectProphecy $eventSerializer;

    protected function setUp(): void
    {
        $this->eventSerializer = $this->prophesize(EventSerializer::class);
    }

    /**
     * @test
     */
    public function it_return_hash_table_name(): void
    {
        $strategy = $this->singleStreamStrategyInstance();
        $streamName = new StreamName('foo-stream');

        $this->assertEquals('_' . sha1($streamName->toString()), $strategy->tableName($streamName));
    }

    /**
     * @test
     */
    public function it_serialize_message_and_return_array(): void
    {
        $message = new Message(SomeEvent::fromPayload(['foo' => 'bar']));

        $serializedMessage = [
            'headers' => [
                MessageHeader::EVENT_ID => Uuid::uuid4(),
                MessageHeader::EVENT_TYPE => 'event.type',
                MessageHeader::TIME_OF_RECORDING => (new SystemClock())->pointInTime()
            ],
            'payload' => [
                'foo' => 'bar'
            ]
        ];

        $expected = [
            'event_id' => $serializedMessage['headers'][MessageHeader::EVENT_ID],
            'event_type' => $serializedMessage['headers'][MessageHeader::EVENT_TYPE],
            'payload' => Json::encode($serializedMessage['payload']),
            'headers' => Json::encode($serializedMessage['headers']),
            'created_at' => (string)$serializedMessage['headers'][MessageHeader::TIME_OF_RECORDING],
        ];

        $strategy = $this->singleStreamStrategyInstance();

        $this->eventSerializer->serializeMessage($message)->willReturn($serializedMessage);

        $arrayMessage = $strategy->serializeMessage($message);

        $this->assertEquals($expected, $arrayMessage);

    }

    private function singleStreamStrategyInstance(): SingleStreamPersistenceStrategy
    {
        $serializer = $this->eventSerializer->reveal();
        return new class($serializer) extends SingleStreamPersistenceStrategy {
            public function up(string $tableName): ?callable
            {
                return function () {
                    //
                };
            }
        };
    }
}
