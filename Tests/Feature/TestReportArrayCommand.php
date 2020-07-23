<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Feature;

use Plexikon\Chronicle\Clock\SystemClock;
use Plexikon\Chronicle\Messaging\Alias\ClassNameMessageAlias;
use Plexikon\Chronicle\Messaging\MessageFactory;
use Plexikon\Chronicle\Messaging\Serializer\MessageSerializer;
use Plexikon\Chronicle\Messaging\Serializer\PayloadSerializer;
use Plexikon\Chronicle\Reporter\Subscribers\MessageFactorySubscriber;
use Plexikon\Chronicle\Reporter\Subscribers\TrackingCommandSubscriber;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Factory\MessageSubscriberFactory;
use Plexikon\Chronicle\Tests\Factory\MessageSubscriberTestFactory;
use Plexikon\Chronicle\Tests\Factory\ReporterFactory;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Plexikon\Chronicle\Tests\Util\HasMessageSubscribersAssertion;
use Ramsey\Uuid\Uuid;

final class TestReportArrayCommand
{
    use HasMessageSubscribersAssertion;

    /**
     * @test
     */
    public function it_dispatch_array_command(): void
    {
        $map = [SomeCommand::class => function (SomeCommand $command): void {
        }];
        $dispatchCommand = $this->arrayCommand();
        $messageAlias = new ClassNameMessageAlias();

        ReporterFactory::withRouter($map, null, null)
            ->withSubscribers(...$this->messageSubscribers($dispatchCommand, $messageAlias))
            ->reportCommand(null)
            ->publish($dispatchCommand);
    }

    private function arrayCommand(): array
    {
        return [
            'headers' => [
                MessageHeader::EVENT_ID => Uuid::uuid4(),
                MessageHeader::EVENT_TYPE => SomeCommand::class,
                MessageHeader::TIME_OF_RECORDING => (new SystemClock())->pointInTime()
            ],
            'payload' => [
                'foo' => 'bar'
            ]
        ];
    }

    private function messageSubscribers(array $command, MessageAlias $messageAlias): array
    {
        return [
            new TrackingCommandSubscriber(),
            $this->messageFactorySubscriberInstance($messageAlias),
            $this->assertMessageAreEqualsSubscriber($command),
            $this->assertMessageHandledSubscriber(),
            ...MessageSubscriberFactory::create($messageAlias)
                ->withDefaultMessageDecorators()->messageSubscribers(),
        ];
    }

    private function assertMessageAreEqualsSubscriber(array $command): MessageSubscriber
    {
        return MessageSubscriberTestFactory::create($this,
            function (TestCase $testCase, MessageContext $context) use ($command) {
                $testCase->assertInstanceof(
                    SomeCommand::class, $context->getMessage()->event()
                );

                $testCase->assertEquals(
                    $command['headers'], $context->getMessage()->headers()
                );

                $testCase->assertEquals(
                    $command['payload'], $context->getMessage()->event()->toPayload()
                );
            })->onFinalize(10);
    }

    private function messageFactorySubscriberInstance(MessageAlias $messageAlias): MessageSubscriber
    {
        return new MessageFactorySubscriber(
            new MessageFactory(
                new MessageSerializer($messageAlias, new PayloadSerializer()),
            ));
    }

}
