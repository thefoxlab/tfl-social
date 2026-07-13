<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Meta;

use JsonSerializable;
use TheFoxLab\TflSocial\Contracts\Arrayable;
use TheFoxLab\TflSocial\Traits\ArrayableTrait;

final class Pagination implements Arrayable, JsonSerializable
{
    use ArrayableTrait;

    public function __construct(
        private readonly ?string $before = null,
        private readonly ?string $after = null,
        private readonly ?string $previous = null,
        private readonly ?string $next = null
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        $paging = is_array($payload['paging'] ?? null) ? $payload['paging'] : [];
        $cursors = is_array($paging['cursors'] ?? null) ? $paging['cursors'] : [];

        return new self(
            before: is_string($cursors['before'] ?? null) ? $cursors['before'] : null,
            after: is_string($cursors['after'] ?? null) ? $cursors['after'] : null,
            previous: is_string($paging['previous'] ?? null) ? $paging['previous'] : null,
            next: is_string($paging['next'] ?? null) ? $paging['next'] : null
        );
    }

    public function before(): ?string
    {
        return $this->before;
    }

    public function after(): ?string
    {
        return $this->after;
    }

    public function previous(): ?string
    {
        return $this->previous;
    }

    public function next(): ?string
    {
        return $this->next;
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
