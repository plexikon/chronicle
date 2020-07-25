<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Messaging\Serializer;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Generator;
use Plexikon\Chronicle\Clock\PointInTime;
use Plexikon\Chronicle\Clock\SystemClock;
use Plexikon\Chronicle\Exception\InvalidArgumentException;
use Plexikon\Chronicle\Exception\RuntimeException;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Messaging\Serializer\EventSerializer;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Messaging\PayloadSerializer;
use Plexikon\Chronicle\Tests\Double\SomeAggregateId;
use Plexikon\Chronicle\Tests\Double\SomeEvent;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class EventSerializerTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideValidTimeOfRecording
     * @param string|DateTimeImmutable|PointInTime $dateTime
     */
    public function it_serialize_event($dateTime): void
    {
        $eventPayload = ['foo' => 'bar'];
        $eventHeaders = [
            MessageHeader::AGGREGATE_ID => $this->aggregateId,
            MessageHeader::TIME_OF_RECORDING => $dateTime
        ];

        $event = SomeEvent::fromPayload($eventPayload);
        $message = new Message($event, $eventHeaders);

        $alias = $this->prophesize(MessageAlias::class);
        $payloadSerializer = $this->prophesize(PayloadSerializer::class);
        $payloadSerializer->serializePayload($event)->willReturn($eventPayload)->shouldBeCalled();

        $expectedTime = null;
        switch ($dateTime) {
            case is_string($dateTime):
                $expectedTime = $dateTime;
                break;
            case $dateTime instanceof DateTimeImmutable:
                $expectedTime = $dateTime->format(PointInTime::DATE_TIME_FORMAT);
                break;
            default:
                $expectedTime = $dateTime->toString();
        }

        $expectedPayload = [
            'payload' => $eventPayload,
            'headers' => [
                MessageHeader::AGGREGATE_ID => $this->aggregateId->toString(),
                MessageHeader::TIME_OF_RECORDING => $expectedTime,
            ]
        ];

        $serializer = new EventSerializer($alias->reveal(), $payloadSerializer->reveal());

        $serializedMessage = $serializer->serializeMessage($message);

        $this->assertEquals($expectedPayload, $serializedMessage);
    }

    /**
     * @test
     * @dataProvider provideInvalidTimeOfRecording
     * @param mixed $invalidDateTime
     */
    public function it_raise_exception_if_time_of_recording_header_is_invalid($invalidDateTime): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to serialize time of recording header');

        try {
            $eventPayload = ['foo' => 'bar'];
            $eventHeaders = [
                MessageHeader::AGGREGATE_ID => $this->aggregateId,
                MessageHeader::TIME_OF_RECORDING => $invalidDateTime
            ];

            $event = SomeEvent::fromPayload($eventPayload);
            $message = new Message($event, $eventHeaders);

            $alias = $this->prophesize(MessageAlias::class);
            $payloadSerializer = $this->prophesize(PayloadSerializer::class);
            $payloadSerializer->serializePayload()->shouldNotBeCalled();

            $serializer = new EventSerializer($alias->reveal(), $payloadSerializer->reveal());

            $serializer->serializeMessage($message);
        } catch (RuntimeException $exception) {
            if (is_string($invalidDateTime)) {
                $this->assertInstanceOf(InvalidArgumentException::class, $exception->getPrevious());
            }

            throw $exception;
        }
    }

    public function provideValidTimeOfRecording(): Generator
    {
        $clock = new SystemClock();

        yield [$clock->pointInTime()];
        yield [$clock->pointInTime()->toString()];
        yield [$clock->dateTime()];
    }

    public function provideInvalidTimeOfRecording(): Generator
    {
        yield [new DateTime('now', new DateTimeZone('UTC'))];
        yield [null];
        yield [''];
        yield ['null'];
        yield ['2020/10/12'];
    }

    private AggregateId $aggregateId;

    protected function setUp(): void
    {
        $this->aggregateId = SomeAggregateId::create();
    }
}
