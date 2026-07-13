<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Facebook;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use TheFoxLab\TflSocial\Contracts\Arrayable;
use TheFoxLab\TflSocial\Traits\ArrayableTrait;
use Traversable;

/**
 * @implements IteratorAggregate<int, Page>
 */
final class PageCollection implements Arrayable, Countable, IteratorAggregate, JsonSerializable
{
    use ArrayableTrait;

    /**
     * @param list<Page> $pages
     */
    public function __construct(
        private readonly array $pages = []
    ) {
    }

    /**
     * @return Traversable<int, Page>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->pages);
    }

    public function count(): int
    {
        return count($this->pages);
    }

    public function first(): ?Page
    {
        return $this->pages[0] ?? null;
    }

    public function find(string $pageId): ?Page
    {
        foreach ($this->pages as $page) {
            if ($page->pageId() === $pageId) {
                return $page;
            }
        }

        return null;
    }

    public function isEmpty(): bool
    {
        return $this->pages === [];
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
