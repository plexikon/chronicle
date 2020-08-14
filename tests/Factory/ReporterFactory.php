<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Factory;

use Illuminate\Contracts\Container\Container;
use Plexikon\Chronicle\Messaging\Alias\ClassNameMessageAlias;
use Plexikon\Chronicle\Messaging\Producer\SyncMessageProducer;
use Plexikon\Chronicle\Reporter\ReportCommand;
use Plexikon\Chronicle\Reporter\ReportEvent;
use Plexikon\Chronicle\Reporter\ReportQuery;
use Plexikon\Chronicle\Reporter\Router\MultipleHandlerRouter;
use Plexikon\Chronicle\Reporter\Router\ReporterRouter;
use Plexikon\Chronicle\Reporter\Router\SingleHandlerRouter;
use Plexikon\Chronicle\Reporter\Subscribers\ReporterRouterSubscriber;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageProducer;
use Plexikon\Chronicle\Support\Contract\Reporter\Router;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\Tracker;
use Plexikon\Chronicle\Tracker\TrackingMessage;

final class ReporterFactory
{
    /**
     * @var callable|null
     */
    private $callableMethod = null;

    /**
     * @var MessageSubscriber[]
     */
    private array $messageSubscribers = [];

    private ?Tracker $tracker = null;
    private ?MessageAlias $messageAlias = null;
    private ?Container $container;
    private ?MessageProducer $messageProducer;
    private array $map;

    public function __construct(array $map, ?Container $container, ?MessageProducer $messageProducer)
    {
        $this->map = $map;
        $this->container = $container;
        $this->messageProducer = $messageProducer;
    }

    public static function withRouter(array $map,
                                      ?Container $container,
                                      ?MessageProducer $messageProducer): self
    {
        return new self($map, $container, $messageProducer ?? new SyncMessageProducer());
    }

    public function withMessageAlias(MessageAlias $messageAlias): self
    {
        $this->messageAlias = $messageAlias;

        return $this;
    }

    public function withSubscribers(MessageSubscriber ...$messageSubscribers): self
    {
        $this->messageSubscribers += $messageSubscribers;

        return $this;
    }

    public function withCallableMethod(string $methodName): self
    {
        $this->callableMethod = $methodName;

        return $this;
    }

    public function reportCommand(?Tracker $tracker): ReportCommand
    {
        $this->tracker = $tracker ?? new TrackingMessage();

        $commandRouterSubscriber = new ReporterRouterSubscriber(
            new SingleHandlerRouter($this->defaultRouterInstance()),
            $this->messageProducer
        );

        $this->messageSubscribers[] = $commandRouterSubscriber;

        $this->attachSubscribers();

        return new ReportCommand(null, $this->tracker);
    }

    public function reportEvent(?Tracker $tracker): ReportEvent
    {
        $this->tracker = $tracker ?? new TrackingMessage();

        $eventRouterSubscriber = new ReporterRouterSubscriber(
            new MultipleHandlerRouter($this->defaultRouterInstance(), true),
            $this->messageProducer
        );

        $this->messageSubscribers[] = $eventRouterSubscriber;

        $this->attachSubscribers();

        return new ReportEvent(null, $this->tracker);
    }

    public function reportQuery(?Tracker $tracker): ReportQuery
    {
        $this->tracker = $tracker ?? new TrackingMessage();

        $queryRouterSubscriber = new ReporterRouterSubscriber(
            new SingleHandlerRouter($this->defaultRouterInstance()),
            $this->messageProducer
        );

        $this->messageSubscribers[] = $queryRouterSubscriber;

        $this->attachSubscribers();

        return new ReportQuery(null, $this->tracker);
    }

    private function defaultRouterInstance(): Router
    {
        return new ReporterRouter(
            $this->map,
            $this->messageAlias ?? $this->messageAlias = new ClassNameMessageAlias(),
            $this->container,
            $this->callableMethod
        );
    }

    private function attachSubscribers(): void
    {
        foreach ($this->messageSubscribers as $messageSubscriber) {
            $messageSubscriber->attachToTracker($this->tracker);
        }
    }
}
