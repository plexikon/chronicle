<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Messaging\Alias;

use Plexikon\Chronicle\Messaging\Alias\InflectorMessageAlias;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class ClassBaseNameInflectorMessageAliasTest extends TestCase
{
    /**
     * ô@test
     */
    public function it_return_event_type_from_class_to_type(): void
    {
        $messageAlias = new InflectorMessageAlias();

        $eventClass = \Plexikon\Chronicle\Tests\Double\SomeCommand::class;
        $eventType = 'plexikon.chronicle.tests.double.some_command';

        $this->assertEquals($eventType, $messageAlias->classToType($eventClass));
    }

    /**
     * ô@test
     */
    public function it_return_class_name_from_type_to_class(): void
    {
        $messageAlias = new InflectorMessageAlias();

        $eventClass = \Plexikon\Chronicle\Tests\Double\SomeCommand::class;
        $eventType = 'plexikon.chronicle.tests.double.some_command';

        $this->assertEquals($eventClass, $messageAlias->typeToClass($eventType));
    }

    /**
     * ô@test
     */
    public function it_return_event_alias_from_class_to_alias(): void
    {
        $messageAlias = new InflectorMessageAlias();

        $eventClass = \Plexikon\Chronicle\Tests\Double\SomeCommand::class;
        $eventAlias = 'some-command';

        $this->assertEquals($eventAlias, $messageAlias->classToAlias($eventClass));
    }

    /**
     * ô@test
     */
    public function it_return_event_type_from_instance_to_type(): void
    {
        $messageAlias = new InflectorMessageAlias();

        $event = SomeCommand::withData(['foo']);
        $eventType = 'plexikon.chronicle.tests.double.some_command';

        $this->assertEquals($eventType, $messageAlias->instanceToType($event));
    }

    /**
     * ô@test
     */
    public function it_return_class_name_from_instance_to_type_with_message(): void
    {
        $messageAlias = new InflectorMessageAlias();

        $eventType = 'plexikon.chronicle.tests.double.some_command';
        $event = SomeCommand::withData(['foo']);
        $message = new Message($event);

        $this->assertEquals($eventType, $messageAlias->instanceToType($message));
    }

    /**
     * ô@test
     */
    public function it_return_class_name_from_instance_to_alias(): void
    {
        $messageAlias = new InflectorMessageAlias();

        $event = SomeCommand::withData(['foo']);
        $eventAlias = 'some-command';

        $this->assertEquals($eventAlias, $messageAlias->instanceToAlias($event));
    }

    /**
     * ô@test
     */
    public function it_return_class_name_from_instance_to_alias_with_message(): void
    {
        $messageAlias = new InflectorMessageAlias();

        $eventAlias = 'some-command';
        $message = new Message(SomeCommand::withData(['foo']));

        $this->assertEquals($eventAlias, $messageAlias->instanceToAlias($message));
    }
}
