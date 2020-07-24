<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Reporter;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\LazyReporter;
use Plexikon\Chronicle\Reporter\ReporterManager;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Double\SomeEvent;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

final class LazyReporterTest extends TestCase
{
    /**
     * @test
     */
    public function it_dispatch_command_from_default_reporter(): void
    {
        $message = new Message(SomeCommand::fromPayload([]));

        $reporter = $this->prophesize(Reporter::class);
        $reporter->publish($message)->shouldBeCalled();

        $manager = $this->prophesize(ReporterManager::class);
        $manager->reportCommand(null)->willReturn($reporter);

        $lazyReporter = new LazyReporter($manager->reveal());
        $lazyReporter->publishCommand($message);
    }

    /**
     * @test
     */
    public function it_dispatch_command_from_named_reporter(): void
    {
        $message = new Message(SomeCommand::fromPayload([]));

        $reporter = $this->prophesize(Reporter::class);
        $reporter->publish($message)->shouldBeCalled();

        $manager = $this->prophesize(ReporterManager::class);
        $manager->reportCommand('foo')->willReturn($reporter);

        $lazyReporter = new LazyReporter($manager->reveal());
        $lazyReporter->withNamedReporter('foo');
        $lazyReporter->publishCommand($message);
    }

    /**
     * @test
     */
    public function it_dispatch_event_from_default_reporter(): void
    {
        $message = new Message(SomeEvent::fromPayload([]));

        $reporter = $this->prophesize(Reporter::class);
        $reporter->publish($message)->shouldBeCalled();

        $manager = $this->prophesize(ReporterManager::class);
        $manager->reportEvent(null)->willReturn($reporter);

        $lazyReporter = new LazyReporter($manager->reveal());
        $lazyReporter->publishEvent($message);
    }

    /**
     * @test
     */
    public function it_dispatch_event_from_named_reporter(): void
    {
        $message = new Message(SomeCommand::fromPayload([]));

        $reporter = $this->prophesize(Reporter::class);
        $reporter->publish($message)->shouldBeCalled();

        $manager = $this->prophesize(ReporterManager::class);
        $manager->reportEvent('foo')->willReturn($reporter);

        $lazyReporter = new LazyReporter($manager->reveal());
        $lazyReporter->withNamedReporter('foo');
        $lazyReporter->publishEvent($message);
    }

    /**
     * @test
     */
    public function it_dispatch_query_from_default_reporter(): void
    {
        $message = new Message(SomeEvent::fromPayload([]));
        $promise = $this->prophesize(PromiseInterface::class)->reveal();

        $reporter = $this->prophesize(Reporter::class);
        $reporter
            ->publish($message)
            ->willReturn($promise)
            ->shouldBeCalled();

        $manager = $this->prophesize(ReporterManager::class);
        $manager->reportQuery(null)->willReturn($reporter);

        $lazyReporter = new LazyReporter($manager->reveal());

        $this->assertEquals($promise, $lazyReporter->publishQuery($message));
    }

    /**
     * @test
     */
    public function it_dispatch_query_from_named_reporter(): void
    {
        $message = new Message(SomeEvent::fromPayload([]));
        $promise = $this->prophesize(PromiseInterface::class)->reveal();

        $reporter = $this->prophesize(Reporter::class);
        $reporter
            ->publish($message)
            ->willReturn($promise)
            ->shouldBeCalled();

        $manager = $this->prophesize(ReporterManager::class);
        $manager->reportQuery('foo')->willReturn($reporter);

        $lazyReporter = new LazyReporter($manager->reveal());
        $lazyReporter->withNamedReporter('foo');

        $this->assertEquals($promise, $lazyReporter->publishQuery($message));
    }

    /**
     * @test
     */
    public function it_handle_promise(): void
    {
        $message = new Message(SomeEvent::fromPayload([]));

        $deferred = new Deferred();
        $deferred->resolve('foo');

        $reporter = $this->prophesize(Reporter::class);
        $reporter
            ->publish($message)
            ->willReturn($deferred->promise())
            ->shouldBeCalled();

        $manager = $this->prophesize(ReporterManager::class);
        $manager->reportQuery(null)->willReturn($reporter);

        $lazyReporter = new LazyReporter($manager->reveal());

        $this->assertEquals('foo', $lazyReporter->handlePromise(
            $lazyReporter->publishQuery($message)
        ));
    }
}
