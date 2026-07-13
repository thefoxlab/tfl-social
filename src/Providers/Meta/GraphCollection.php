<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Meta;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use TheFoxLab\TflSocial\Contracts\Arrayable;
use TheFoxLab\TflSocial\Traits\ArrayableTrait;
use Traversable;

/**
 * @implements IteratorAggregate<int, GraphItem>
 */
final class GraphCollection implements Arrayable, Countable, IteratorAggregate, JsonSerializable
{
    use ArrayableTrait;

    /**
     * @param list<GraphItem> $items
     */
    public function __construct(
        private readonly array $items,
        private readonly Pagination $pagination
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $items = [];

        foreach ($data as $item) {
            if (is_array($item)) {
                $items[] = new GraphItem($item);
            }
        }

        return new self($items, Pagination::fromPayload($payload));
    }

    /**
     * @return Traversable<int, GraphItem>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function first(): ?GraphItem
    {
        return $this->items[0] ?? null;
    }

    public function pagination(): Pagination
    {
        return $this->pagination;
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
