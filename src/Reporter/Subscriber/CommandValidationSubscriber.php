<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter\Subscriber;

use Illuminate\Contracts\Validation\Factory;
use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Exception\MessageValidationFailed;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Messaging\PreValidateMessage;
use Plexikon\Chronicle\Support\Contract\Messaging\ValidateMessage;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\Tracker;

final class CommandValidationSubscriber implements MessageSubscriber
{
    private Factory $validator;

    public function __construct(Factory $validator)
    {
        $this->validator = $validator;
    }

    public function attachToTracker(Tracker $tracker): void
    {
        $tracker->listen(Reporter::DISPATCH_EVENT, function (MessageContext $context): void {
            $message = $context->getMessage();

            if (!$message->isMessaging()) {
                return;
            }

            $event = $message->eventWithHeaders();

            if ($event instanceof ValidateMessage) {
                $alreadyProducedAsync = $message->header(MessageHeader::MESSAGE_ASYNC_MARKED);

                Assertion::notNull($alreadyProducedAsync, 'Validate message require an async marker header');

                if ($alreadyProducedAsync || $event instanceof PreValidateMessage) {
                    $this->validateMessage($message);
                }
            }
        },80000);
    }

    private function validateMessage(Message $message): void
    {
        $validator = $this->validator->make(
            $message->event()->toPayload(),
            $message->event()->validationRules()
        );

        if ($validator->fails()) {
            throw MessageValidationFailed::withValidator($validator, $message);
        }
    }
}
