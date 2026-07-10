<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Instagram;

use RuntimeException;

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

    public static function notImplemented(): self
    {
        return new self('Instagram OAuth token exchange is not implemented yet.');
    }
}
