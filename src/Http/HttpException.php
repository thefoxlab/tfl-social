<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Http;

use RuntimeException;
use Throwable;

final class HttpException extends RuntimeException
{
    public static function configuration(string $message): self
    {
        return new self($message);
    }

    public static function transport(string $message, ?Throwable $previous = null): self
    {
        return new self($message, 0, $previous);
    }

    public static function response(Response $response): self
    {
        return new self(sprintf(
            'HTTP request failed with status code [%d].',
            $response->statusCode()
        ), $response->statusCode());
    }
}
