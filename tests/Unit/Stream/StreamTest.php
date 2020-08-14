<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Stream;

use Generator;
use Plexikon\Chronicle\Stream\Stream;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class StreamTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideIterableMessages
     * @param iterable $messages
     */
    public function it_generate_stream_events(iterable $messages): void
    {
        $stream = new Stream(new StreamName('test-stream'), $messages);

        $result = iterator_to_array($stream->events());

        $this->assertEquals($messages, $result);
    }

    /**
     * @test
     */
    public function it_return_number_of_events(): void
    {
        $messages = ['foo', 'bar', 'baz'];

        $stream = new Stream(new StreamName('test-stream'), $messages);

        $result = $stream->events();

        foreach ($result as $event) {
            //
        }

        $this->assertEquals(3, $result->getReturn());
    }

    public function provideIterableMessages(): Generator
    {
        yield [[]];

        yield [['foo', 'bar']];
    }
}
