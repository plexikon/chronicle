<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Messaging\Alias;

use Plexikon\Chronicle\Messaging\Alias\ClassNameMessageAlias;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class ClassNameMessageAliasTest extends TestCase
{
    /**
     * ô@test
     */
    public function it_return_class_name_from_class_to_type(): void
    {
        $messageAlias = new ClassNameMessageAlias();

        $eventClass = SomeCommand::class;

        $this->assertEquals($eventClass, $messageAlias->classToType($eventClass));
    }

    /**
     * ô@test
     */
    public function it_return_class_name_from_type_to_class(): void
    {
        $messageAlias = new ClassNameMessageAlias();

        $eventClass = SomeCommand::class;

        $this->assertEquals($eventClass, $messageAlias->typeToClass($eventClass));
    }

    /**
     * ô@test
     */
    public function it_return_class_name_from_class_to_alias(): void
    {
        $messageAlias = new ClassNameMessageAlias();

        $eventClass = SomeCommand::class;

        $this->assertEquals($eventClass, $messageAlias->classToAlias($eventClass));
    }

    /**
     * ô@test
     */
    public function it_return_class_name_from_instance_to_type(): void
    {
        $messageAlias = new ClassNameMessageAlias();

        $event = SomeCommand::withData(['foo']);

        $this->assertEquals(SomeCommand::class, $messageAlias->instanceToType($event));
    }

    /**
     * ô@test
     */
    public function it_return_class_name_from_instance_to_type_with_message(): void
    {
        $messageAlias = new ClassNameMessageAlias();

        $event = SomeCommand::withData(['foo']);
        $message = new Message($event);

        $this->assertEquals(SomeCommand::class, $messageAlias->instanceToType($message));
    }

    /**
     * ô@test
     */
    public function it_return_class_name_from_instance_to_alias(): void
    {
        $messageAlias = new ClassNameMessageAlias();

        $event = SomeCommand::withData(['foo']);

        $this->assertEquals(SomeCommand::class, $messageAlias->instanceToAlias($event));
    }

    /**
     * ô@test
     */
    public function it_return_class_name_from_instance_to_alias_with_message(): void
    {
        $messageAlias = new ClassNameMessageAlias();

        $event = SomeCommand::withData(['foo']);
        $message = new Message($event);

        $this->assertEquals(SomeCommand::class, $messageAlias->instanceToAlias($message));
    }
}
