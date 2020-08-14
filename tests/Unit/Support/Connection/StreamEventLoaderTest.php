<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Support\Connection;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\LazyCollection;
use Plexikon\Chronicle\Exception\QueryFailure;
use Plexikon\Chronicle\Exception\StreamNotFound;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Connection\StreamEventLoader;
use Plexikon\Chronicle\Support\Contract\Messaging\EventSerializer;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Tests\Double\SomeEvent;
use Plexikon\Chronicle\Tests\Double\SomePDOException;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use stdClass;

final class StreamEventLoaderTest extends TestCase
{
    /**
     * @test
     */
    public function it_query_events(): void
    {
        $event = new stdClass();
        $event->headers = '{"baz":"foo_bar"}';
        $event->payload = '{"foo":"bar"}';
        $event->no = 1;

        $data = [
            'headers' => ['baz' => 'foo_bar'],
            'payload' => ['foo' => 'bar'],
            'no' => 1
        ];

        $eventMessage = new Message(
            SomeEvent::fromPayload($data['payload']),
            array_merge([MessageHeader::INTERNAL_POSITION => 1], $data['headers'])
        );

        $serializer = $this->prophesize(EventSerializer::class);
        $serializer->unserializePayload($data)->willYield([$eventMessage]);

        $builder = $this->prophesize(Builder::class);

        $collection = new LazyCollection([$event]);
        $builder->cursor()->willReturn($collection)->shouldBeCalled();

        $loader = new StreamEventLoader($serializer->reveal());

        $messages = $loader->query($builder->reveal(), new StreamName('foo'));

        foreach ($messages as $message) {
            $this->assertEquals($eventMessage, $message);
        }

        $this->assertEquals(1, $messages->getReturn());
    }

    /**
     * @test
     */
    public function it_raise_exception_with_empty_events(): void
    {
        $this->expectException(StreamNotFound::class);

        $serializer = $this->prophesize(EventSerializer::class);
        $serializer->unserializePayload()->shouldNotBeCalled();

        $builder = $this->prophesize(Builder::class);

        $collection = new LazyCollection([]);
        $builder->cursor()->willReturn($collection)->shouldBeCalled();

        $loader = new StreamEventLoader($serializer->reveal());

        $loader->query($builder->reveal(), new StreamName('foo'))->current();
    }

    /**
     * @test
     */
    public function it_raise_exception_on_stream_not_found(): void
    {
        $this->markTestIncomplete('fix exceptions in stream event loader');

        $this->expectException(StreamNotFound::class);

        $event = new stdClass();
        $event->headers = '{"baz":"foo_bar"}';
        $event->payload = '{"foo":"bar"}';
        $event->no = 1;

        $data = [
            'headers' => ['baz' => 'foo_bar'],
            'payload' => ['foo' => 'bar'],
            'no' => 1
        ];

        $eventMessage = new Message(
            SomeEvent::fromPayload($data['payload']),
            array_merge([MessageHeader::INTERNAL_POSITION => 1], $data['headers'])
        );

        $previousException = new \RuntimeException('foo', 0);
        $exception = new QueryException('no_sql', [], $previousException);

        $serializer = $this->prophesize(EventSerializer::class);
        $serializer->unserializePayload($data)->willYield([$eventMessage]);

        $collection = new LazyCollection([$event]);

        $builder = $this->prophesize(Builder::class);
        $builder
            ->cursor()
            ->willReturn($collection)
            ->willThrow($exception);

        $loader = new StreamEventLoader($serializer->reveal());

        $loader->query($builder->reveal(), new StreamName('foo'))->current();
    }

    /**
     * @test
     */
    public function it_raise_exception_on_internal_query_exception(): void
    {
        $this->markTestIncomplete('fix exceptions in stream event loader');

        $this->expectException(QueryFailure::class);

        $event = new stdClass();
        $event->headers = '{"baz":"foo_bar"}';
        $event->payload = '{"foo":"bar"}';
        $event->no = 1;

        $data = [
            'headers' => ['baz' => 'foo_bar'],
            'payload' => ['foo' => 'bar'],
            'no' => 1
        ];

        $eventMessage = new Message(
            SomeEvent::fromPayload($data['payload']),
            array_merge([MessageHeader::INTERNAL_POSITION => 1], $data['headers'])
        );

        $previousException = new SomePDOException('42S22');
        $exception = new QueryException('no_sql', [], $previousException);

        $serializer = $this->prophesize(EventSerializer::class);
        $serializer->unserializePayload($data)->willYield([$eventMessage]);

        $collection = new LazyCollection([$event]);

        $builder = $this->prophesize(Builder::class);
        $builder
            ->cursor()
            ->willReturn($collection)
            ->willThrow($exception);

        $loader = new StreamEventLoader($serializer->reveal());

        $loader->query($builder->reveal(), new StreamName('foo'))->current();
    }

    /**
     * @test
     */
    public function it_raise_exception_on_global_exception(): void
    {
        $this->markTestIncomplete('fix exceptions in stream event loader');

        $this->expectException(QueryFailure::class);

        $event = new stdClass();
        $event->headers = '{"baz":"foo_bar"}';
        $event->payload = '{"foo":"bar"}';
        $event->no = 1;

        $data = [
            'headers' => ['baz' => 'foo_bar'],
            'payload' => ['foo' => 'bar'],
            'no' => 1
        ];

        $eventMessage = new Message(
            SomeEvent::fromPayload($data['payload']),
            array_merge([MessageHeader::INTERNAL_POSITION => 1], $data['headers'])
        );

        $previousException = new SomePDOException('00000');
        $exception = new QueryException('no_sql', [], $previousException);

        $serializer = $this->prophesize(EventSerializer::class);
        $serializer->unserializePayload($data)->willYield([$eventMessage]);

        $collection = new LazyCollection([$event]);

        $builder = $this->prophesize(Builder::class);
        $builder
            ->cursor()
            ->willReturn($collection)
            ->willThrow($exception);

        $loader = new StreamEventLoader($serializer->reveal());

        $loader->query($builder->reveal(), new StreamName('foo'))->current();
    }

    private function generateEvents(int $num): array
    {
        $events = [];

        while ($num !== 0) {
            $event = new stdClass();
            $event->headers = '{}';
            $event->payload = '{"foo":"bar"}';
            $event->no = $num;

            --$num;
        }

        ksort($events);

        return $events;
    }
}
