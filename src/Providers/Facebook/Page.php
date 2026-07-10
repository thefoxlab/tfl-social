<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Facebook;

use ArrayIterator;

final class Page
{
    /**
     * @param list<string> $tasks
     */
    public function __construct(
        private readonly string $pageId,
        private readonly string $name,
        private readonly string $accessToken,
        private readonly ?string $category = null,
        private readonly array $tasks = [],
        private readonly ?string $picture = null
    ) {
    }

    public function pageId(): string
    {
        return $this->pageId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    public function category(): ?string
    {
        return $this->category;
    }

    /**
     * @return ArrayIterator<int, string>
     */
    public function tasks(): ArrayIterator
    {
        return new ArrayIterator($this->tasks);
    }

    public function picture(): ?string
    {
        return $this->picture;
    }
}
