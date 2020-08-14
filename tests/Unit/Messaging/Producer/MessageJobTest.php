<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Messaging\Producer;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Queue;
use Plexikon\Chronicle\Messaging\Producer\MessageJob;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class MessageJobTest extends TestCase
{
    /**
     * @test
     */
    public function it_handle_message_job(): void
    {
        $payload = ['foo'];

        $reporter = $this->prophesize(Reporter::class);
        $reporter->publish($payload)->shouldBeCalled();

        $container = new Container();
        $container->instance('reporter', $reporter->reveal());

        $job = new MessageJob($payload, 'reporter', null, null);

        $job->handle($container);
    }

    /**
     * @test
     */
    public function it_push_job_on_queue(): void
    {
        $job = new MessageJob(['bar'], 'reporter', null, 'named_queue');

        $queue = $this->prophesize(Queue::class);
        $queue->pushOn('named_queue', $job)->shouldBeCalled();

        $job->queue($queue->reveal(), $job);
    }

    /**
     * @test
     */
    public function it_access_public_properties(): void
    {
        $job = new MessageJob(['foo'], 'reporter', 'named_connection', 'named_queue');

        $this->assertEquals(['foo'], $job->payload);
        $this->assertEquals('reporter', $job->busType);
        $this->assertEquals('named_connection', $job->connection);
        $this->assertEquals('named_queue', $job->queue);
    }

    /**
     * @test
     */
    public function it_access_to_event_name(): void
    {
        $job = new MessageJob([
            'payload' => ['foo'],
            'headers' => [
                MessageHeader::EVENT_TYPE => 'foo.bar'
            ]
        ], 'reporter', null, null);

        $this->assertEquals('foo.bar', $job->displayName());
    }
}
