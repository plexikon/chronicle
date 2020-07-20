<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support;

use React\Promise\PromiseInterface;
use Throwable;

trait HasPromiseHandler
{
    /**
     * @param PromiseInterface $promise
     * @param bool $raiseException
     * @return mixed
     * @throws Throwable
     */
    public function handlePromise(PromiseInterface $promise, bool $raiseException = true)
    {
        $exception = null;
        $result = null;

        $promise->then(
            static function ($data) use (&$result) {
                $result = $data;
            },
            static function ($exc) use (&$exception) {
                $exception = $exc;
            }
        );

        if ($raiseException && $exception instanceof Throwable) {
            throw $exception;
        }

        return $exception ?? $result;
    }
}
