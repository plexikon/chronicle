<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter;

use Plexikon\Chronicle\Support\HasPromiseHandler;
use React\Promise\PromiseInterface;

final class LazyReporter
{
    use HasPromiseHandler;

    private ?string $reporterName = null;
    private ReporterManager $reporterManager;

    public function __construct(ReporterManager $reporterManager)
    {
        $this->reporterManager = $reporterManager;
    }

    public function publishCommand($command): void
    {
        $this->reporterManager->reportCommand($this->reporterName)->publish($command);

        $this->reporterName = null;
    }

    public function publishEvent($event): void
    {
        $this->reporterManager->reportEvent($this->reporterName)->publish($event);

        $this->reporterName = null;
    }

    public function publishQuery($query): PromiseInterface
    {
        $promise = $this->reporterManager->reportQuery($this->reporterName)->publish($query);

        $this->reporterName = null;

        return $promise;
    }

    public function withNamedReporter(string $reporterName): self
    {
        $this->reporterName = $reporterName;

        return $this;
    }
}
