<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Messaging\Producer;

use Illuminate\Contracts\Bus\QueueingDispatcher;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Messaging\Producer\IlluminateProducer;
use Plexikon\Chronicle\Messaging\Producer\MessageJob;
use Plexikon\Chronicle\Reporter\ReportCommand;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageSerializer;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

final class IlluminateProducerTest extends TestCase
{
    /**
     * @test
     * @dataProvider providePayload
     * @param array $payload
     */
    public function it_dispatch_to_queue(array $payload): void
    {
        $message = new Message(SomeCommand::fromPayload(['foo' => 'bar']));

        $this->messageSerializer->serializeMessage($message)->willReturn($payload)->shouldBeCalled();

        $this->queue->dispatchToQueue(Argument::that(function (MessageJob $job)use($payload) {
            $this->assertEquals('connection_name', $job->connection);
            $this->assertEquals('queue_name', $job->queue);
            $this->assertEquals(ReportCommand::class, $job->busType);
            $this->assertEquals($payload, $job->payload);

            return $job;
        }))->shouldBeCalled();

        $producer = new IlluminateProducer(
            $this->queue->reveal(),
            $this->messageSerializer->reveal(),
            'connection_name',
            'queue_name',
        );

        $producer->handle($message);
    }

    private ObjectProphecy $queue;
    private ObjectProphecy $messageSerializer;

    protected function setUp(): void
    {
        $this->queue = $this->prophesize(QueueingDispatcher::class);
        $this->messageSerializer = $this->prophesize(MessageSerializer::class);
    }

    public function providePayload(): \Generator
    {
        yield [
            $detectBusFromHeader = [
                'headers' => [
                    MessageHeader::MESSAGE_BUS_TYPE => ReportCommand::class,
                ],
                'payload' => [
                    'foo' => 'bar'
                ],
            ]
        ];

        yield [
            $detectBusFromEvent = [
                'headers' => [],
                'payload' => [
                    'foo' => 'bar'
                ]
            ]
        ];
    }
}
