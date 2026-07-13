<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Meta;

use JsonSerializable;
use TheFoxLab\TflSocial\Contracts\Arrayable;
use TheFoxLab\TflSocial\Traits\ArrayableTrait;

final class GraphRequestOptions implements Arrayable, JsonSerializable
{
    use ArrayableTrait;

    /**
     * @param list<string> $fields
     */
    public function __construct(
        private readonly array $fields = [],
        private readonly ?int $limit = null,
        private readonly ?string $after = null,
        private readonly ?string $before = null
    ) {
    }

    /**
     * @param list<string>|string|null $fields
     */
    public static function make(array|string|null $fields = null, ?int $limit = null, ?string $after = null, ?string $before = null): self
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }

        return new self($fields ?? [], $limit, $after, $before);
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        $query = [];

        if ($this->fields !== []) {
            $query['fields'] = implode(',', $this->fields);
        }

        if ($this->limit !== null) {
            $query['limit'] = $this->limit;
        }

        if ($this->after !== null) {
            $query['after'] = $this->after;
        }

        if ($this->before !== null) {
            $query['before'] = $this->before;
        }

        return $query;
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
