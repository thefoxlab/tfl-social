<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Facebook;

use RuntimeException;
use Throwable;

final class OAuthException extends RuntimeException
{
    public static function configuration(string $message): self
    {
        return new self($message);
    }

    public static function invalidState(): self
    {
        return new self('OAuth callback state is invalid.');
    }

    public static function requestFailed(string $message, ?Throwable $previous = null): self
    {
        return new self($message, 0, $previous);
    }

    public static function invalidResponse(string $message): self
    {
        return new self($message);
    }
}
