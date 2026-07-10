<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial;

use DateTimeInterface;
use TheFoxLab\TflSocial\Contracts\FeedBuilderInterface;

final class FeedBuilder implements FeedBuilderInterface
{
    private ?int $account = null;

    /**
     * @var list<int>
     */
    private array $accounts = [];

    /**
     * @var list<string>
     */
    private array $platforms = [];

    private ?string $type = null;

    private DateTimeInterface|string|null $from = null;

    private DateTimeInterface|string|null $to = null;

    private ?int $limit = null;

    private ?int $offset = null;

    private ?string $orderBy = null;

    public function account(int $account): self
    {
        $this->account = $account;

        return $this;
    }

    /**
     * @param list<int> $accounts
     */
    public function accounts(array $accounts): self
    {
        $this->accounts = $accounts;

        return $this;
    }

    public function all(): self
    {
        $this->account = null;
        $this->accounts = [];

        return $this;
    }

    /**
     * @param list<string> $platforms
     */
    public function platform(array $platforms): self
    {
        $this->platforms = $platforms;

        return $this;
    }

    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function from(DateTimeInterface|string $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function to(DateTimeInterface|string $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function orderBy(string $field): self
    {
        $this->orderBy = $field;

        return $this;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function latest(): array
    {
        return $this->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function oldest(): array
    {
        return $this->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function get(): array
    {
        return [];
    }
}
