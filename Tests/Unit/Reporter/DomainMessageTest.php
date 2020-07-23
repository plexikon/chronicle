<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Reporter;

use Plexikon\Chronicle\Tests\Double\SomeDomainMessage;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class DomainMessageTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_instantiated(): void
    {
        $message = SomeDomainMessage::fromPayload(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $message->toPayload());
    }

    /**
     * @test
     */
    public function it_set_and_retrieve_headers(): void
    {
        $message = SomeDomainMessage::fromPayload([]);

        $this->assertEmpty($message->headers());

        $newMessage = $message->withHeaders(['foo' => 'bar']);

        $this->assertEmpty($message->headers());

        $this->assertEquals(['foo' => 'bar'], $newMessage->headers());

        $this->assertEquals('bar', $newMessage->header('foo'));

        $this->assertNull($newMessage->header('bar'));
    }
}
