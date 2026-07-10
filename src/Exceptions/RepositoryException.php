<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Exceptions;

use RuntimeException;

final class RepositoryException extends RuntimeException
{
    /**
     * @param array<string, string> $errors
     */
    public static function forModelFailure(string $operation, array $errors = []): self
    {
        if ($errors === []) {
            return new self(sprintf('Repository %s operation failed.', $operation));
        }

        return new self(sprintf(
            'Repository %s operation failed: %s',
            $operation,
            implode('; ', $errors)
        ));
    }
}
