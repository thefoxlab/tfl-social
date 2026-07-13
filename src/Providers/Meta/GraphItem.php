<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Meta;

use JsonSerializable;
use TheFoxLab\TflSocial\Contracts\Arrayable;
use TheFoxLab\TflSocial\Traits\ArrayableTrait;

final class GraphItem implements Arrayable, JsonSerializable
{
    use ArrayableTrait;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private readonly array $attributes
    ) {
    }

    public function id(): ?string
    {
        $id = $this->attributes['id'] ?? null;

        return is_string($id) ? $id : null;
    }

    public function get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
