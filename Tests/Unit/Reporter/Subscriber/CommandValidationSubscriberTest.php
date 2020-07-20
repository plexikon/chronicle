<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Reporter\Subscriber;

use Assert\AssertionFailedException;
use Generator;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Plexikon\Chronicle\Exception\MessageValidationFailed;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\Subscribers\CommandValidationSubscriber;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Messaging\ValidateMessage;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Double\SomeCommandToPreValidate;
use Plexikon\Chronicle\Tests\Double\SomeCommandToValidate;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Plexikon\Chronicle\Tracker\DefaultMessageContext;
use Plexikon\Chronicle\Tracker\TrackingReport;

final class CommandValidationSubscriberTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideMessageToValidate
     * @param ValidateMessage $event
     * @param bool $asyncMarker
     */
    public function it_subscribe_to_tracker_and_validate_message(ValidateMessage $event, bool $asyncMarker): void
    {
        $validator = $this->prophesize(Validator::class);
        $validator->fails()->willReturn(false)->shouldBeCalled();

        $factory = $this->prophesize(Factory::class);
        $factory->make(['baz' => 'foo_bar'], $event->validationRules())
            ->willReturn($validator)
            ->shouldBeCalled();

        $message = new Message($event, [MessageHeader::MESSAGE_ASYNC_MARKED => $asyncMarker]);

        $this->handleSubscriber($factory->reveal(), $message);
    }

    /**
     * @test
     * @dataProvider provideMessageNotToValidate
     * @param object $event
     */
    public function it_subscribe_to_tracker_and_does_not_validate_message(object $event): void
    {
        $factory = $this->prophesize(Factory::class);
        $factory->make()->shouldNotBeCalled();

        $message = new Message($event);

        $this->handleSubscriber($factory->reveal(), $message);
    }

    /**
     * @test
     * @dataProvider provideMessageToDeferValidation
     * @param ValidateMessage $event
     * @param bool $asyncMarker
     */
    public function it_subscribe_to_tracker_and_defer_validation_message(ValidateMessage $event, bool $asyncMarker): void
    {
        $factory = $this->prophesize(Factory::class);
        $factory->make()->shouldNotBeCalled();

        $message = new Message($event, [MessageHeader::MESSAGE_ASYNC_MARKED => $asyncMarker]);

        $this->handleSubscriber($factory->reveal(), $message);
    }

    /**
     * @test
     * @dataProvider provideMessageToValidate
     * @param ValidateMessage $event
     * @param bool $asyncMarker
     */
    public function it_subscribe_to_tracker_and_validate_message_and_raise_validation_exception(ValidateMessage $event, bool $asyncMarker): void
    {
        $this->expectException(MessageValidationFailed::class);

        $validator = $this->prophesize(Validator::class);
        $validator->fails()->willReturn(true)->shouldBeCalled();

        $validator->errors()->shouldBeCalled();

        $factory = $this->prophesize(Factory::class);
        $factory->make(['baz' => 'foo_bar'], $event->validationRules())
            ->willReturn($validator)
            ->shouldBeCalled();

        $message = new Message($event, [MessageHeader::MESSAGE_ASYNC_MARKED => $asyncMarker]);

        $this->handleSubscriber($factory->reveal(), $message);
    }

    /**
     * @test
     * @dataProvider provideMessageToValidate
     * @param ValidateMessage $event
     */
    public function it_raise_exception_if_async_marker_header_is_not_present(ValidateMessage $event): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Validate message require an async marker header');

        $factory = $this->prophesize(Factory::class);
        $factory->make()->shouldNotBeCalled();

        $message = new Message($event, []);

        $this->handleSubscriber($factory->reveal(), $message);
    }

    public function provideMessageToValidate(): Generator
    {
        yield [SomeCommandToValidate::fromPayload(['baz' => 'foo_bar']), $asyncMarker = true];

        yield [SomeCommandToPreValidate::fromPayload(['baz' => 'foo_bar']), $asyncMarker = false];
    }

    public function provideMessageToDeferValidation(): Generator
    {
        yield [SomeCommandToValidate::fromPayload(['baz' => 'foo_bar']), $asyncMarker = false];
    }

    public function provideMessageNotToValidate(): Generator
    {
        yield [new \stdClass()];

        yield [SomeCommand::fromPayload(['foo'])];
    }

    private function handleSubscriber(Factory $factory, Message $message): void
    {
        $subscriber = new CommandValidationSubscriber($factory);

        $context = new DefaultMessageContext(Reporter::DISPATCH_EVENT);
        $context->withMessage($message);

        $tracker = new TrackingReport();
        $subscriber->attachToTracker($tracker);

        $tracker->fire($context);
    }
}
