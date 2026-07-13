<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Http;

use JsonException;
use JsonSerializable;
use TheFoxLab\TflSocial\Contracts\Arrayable;
use TheFoxLab\TflSocial\Traits\ArrayableTrait;

use function json_decode;

use const JSON_THROW_ON_ERROR;

final class Response implements Arrayable, JsonSerializable
{
    use ArrayableTrait;

    /**
     * @param array<string, list<string>> $headers
     */
    public function __construct(
        private readonly int $statusCode,
        private readonly string $body,
        private readonly array $headers = [],
        private readonly ?string $reason = null
    ) {
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function body(): string
    {
        return $this->body;
    }

    /**
     * @return array<string, list<string>>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $name): ?string
    {
        $normalized = strtolower($name);

        foreach ($this->headers as $header => $values) {
            if (strtolower($header) === $normalized) {
                return implode(', ', $values);
            }
        }

        return null;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }

    public function successful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function json(): array
    {
        $decoded = json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
