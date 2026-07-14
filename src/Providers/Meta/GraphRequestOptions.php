<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Meta;

use JsonSerializable;
use TheFoxLab\TflSocial\Contracts\Arrayable;
use TheFoxLab\TflSocial\Traits\ArrayableTrait;

use function array_key_exists;
use function implode;
use function is_int;
use function is_array;
use function is_string;

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
     * @param array<string, mixed>|list<string>|string|null $fields
     */
    public static function make(array|string|null $fields = null, ?int $limit = null, ?string $after = null, ?string $before = null): self
    {
        if (is_array($fields) && array_key_exists('fields', $fields)) {
            $limit = is_int($fields['limit'] ?? null) ? $fields['limit'] : $limit;
            $after = is_string($fields['after'] ?? null) ? $fields['after'] : $after;
            $before = is_string($fields['before'] ?? null) ? $fields['before'] : $before;
            $fields = $fields['fields'];
        }

        if (is_string($fields)) {
            $fields = [$fields];
        }

        return new self(is_array($fields) ? self::normalizeFields($fields) : [], $limit, $after, $before);
    }

    /**
     * @param list<string> $fields
     */
    public function withDefaultFields(array $fields): self
    {
        if ($this->fields !== []) {
            return $this;
        }

        return new self($fields, $this->limit, $this->after, $this->before);
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

    /**
     * @param array<mixed> $fields
     *
     * @return list<string>
     */
    private static function normalizeFields(array $fields): array
    {
        $normalized = [];

        foreach ($fields as $field) {
            if (is_string($field) && $field !== '') {
                $normalized[] = $field;
            }
        }

        return $normalized;
    }
}
