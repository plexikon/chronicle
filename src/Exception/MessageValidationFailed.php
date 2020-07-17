<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Exception;

use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Contracts\Validation\Validator;
use Plexikon\Chronicle\Messaging\Message;

final class MessageValidationFailed extends RuntimeException
{
    private static ?Validator $validator;
    private static ?Message $validatedMessage;

    public static function withValidator(Validator $validator, Message $validatedMessage): self
    {
        self::$validator = $validator;
        self::$validatedMessage = $validatedMessage;

        $message = "Validation rules fails:\n";
        $message .= $validator->errors();

        return new self($message);
    }

    public function getValidator(): Validator
    {
        return static::$validator;
    }

    public function failedValidatedMessage(): Message
    {
        return static::$validatedMessage;
    }

    public function errors(): MessageBag
    {
        return $this->getValidator()->errors();
    }
}
