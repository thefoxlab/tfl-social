<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Contracts;

use DateTimeInterface;

interface FeedBuilderInterface
{
    public function account(int $account): self;

    /**
     * @param list<int> $accounts
     */
    public function accounts(array $accounts): self;

    public function all(): self;

    /**
     * @param list<string> $platforms
     */
    public function platform(array $platforms): self;

    public function type(string $type): self;

    public function from(DateTimeInterface|string $from): self;

    public function to(DateTimeInterface|string $to): self;

    public function limit(int $limit): self;

    public function offset(int $offset): self;

    public function orderBy(string $field): self;

    /**
     * @return list<array<string, mixed>>
     */
    public function latest(): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function oldest(): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function get(): array;
}
