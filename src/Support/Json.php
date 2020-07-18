<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support;

use Plexikon\Chronicle\Exception\RuntimeException;

final class Json
{
    /**
     * @param mixed $value
     * @return string
     * @throws RuntimeException
     */
    public static function encode($value): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;

        $json = json_encode($value, $flags);

        if (JSON_ERROR_NONE !== $error = json_last_error()) {
            throw new RuntimeException(json_last_error_msg(), $error);
        }

        return $json;
    }

    /**
     * @param string $json
     * @return mixed
     * @throws RuntimeException
     */
    public static function decode(string $json)
    {
        $data = json_decode($json, true, 512, JSON_BIGINT_AS_STRING);

        if (JSON_ERROR_NONE !== $error = json_last_error()) {
            throw new RuntimeException(json_last_error_msg(), $error);
        }

        return $data;
    }
}
