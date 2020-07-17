<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Util;

use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Tests\Factory\MessageSubscriberTestFactory;
use Plexikon\Chronicle\Tests\Unit\TestCase;

trait HasMessageSubscribersAssertion
{
    protected function assertMessageHandledSubscriber(): MessageSubscriber
    {
        return MessageSubscriberTestFactory::create($this, function (TestCase $testCase, MessageContext $context) {
            $testCase->assertTrue($context->isMessageHandled());
        })->onFinalize(0);
    }

    protected function assertExceptionExistsSubscriber(): MessageSubscriber
    {
        return MessageSubscriberTestFactory::create($this, function (TestCase $testCase, MessageContext $context) {
            $testCase->assertTrue($context->hasException());
        })->onFinalize(10);
    }
}
