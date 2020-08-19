<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Support\Projector;

use Generator;
use Plexikon\Chronicle\Exception\StreamNotFound;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Projector\StreamEventIterator;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use stdClass;

final class StreamEventIteratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_iterate_over_messages(): void
    {
        $iterator = new StreamEventIterator($this->generateValidMessage());

        $count = 0;
        foreach ($iterator as $message) {
            $count++;
        }

        $this->assertEquals(2, $count);
    }

    /**
     * @test
     */
    public function it_use_internal_position_as_iterator_key(): void
    {
        $iterator = new StreamEventIterator($this->generateValidMessage());

        $count = 0;
        foreach ($iterator as $key => $message) {
            $count === 0
                ? $this->assertEquals(5, $key)
                : $this->assertEquals(10, $key);
            $count++;
        }
    }

    /**
     * @test
     */
    public function it_catch_stream_not_found_exception_on_empty_messages(): void
    {
        $generator = $this->generateEmptyGenerator();
        $iterator = new StreamEventIterator($generator);

        $this->assertNull($iterator->current());

    }

    private function generateValidMessage(): Generator
    {
        yield new Message(new stdClass(), [MessageHeader::INTERNAL_POSITION => 5]);
        yield new Message(new stdClass(), [MessageHeader::INTERNAL_POSITION => 10]);
    }

    private function generateEmptyGenerator(): Generator
    {
        yield from [];
        throw new StreamNotFound('foo');
    }
}
