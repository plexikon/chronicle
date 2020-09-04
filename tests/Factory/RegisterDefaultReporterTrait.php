<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Factory;

use Illuminate\Contracts\Foundation\Application;
use Plexikon\Chronicle\Reporter\ReportCommand;
use Plexikon\Chronicle\Reporter\ReporterManager;
use Plexikon\Chronicle\Reporter\ReportEvent;
use Plexikon\Chronicle\Reporter\ReportQuery;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;

trait RegisterDefaultReporterTrait
{
    /**
     * @var Application
     */
    protected $app;

    protected function registerReporters(): void
    {
        $this->app->bind(
            ReportCommand::class,
            fn(Application $app) => $app->get(ReporterManager::class)->reportCommand('default')
        );

        $this->app->bind(
            ReportEvent::class,
            fn(Application $app) => $app->get(ReporterManager::class)->reportEvent('default')
        );

        $this->app->bind(
            ReportQuery::class,
            fn(Application $app) => $app->get(ReporterManager::class)->reportQuery('default')
        );
    }

    protected function messageSubscriberFactory(?MessageAlias $messageAlias): MessageSubscriberFactory
    {
        return MessageSubscriberFactory::create($messageAlias);
    }
}
