<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Instagram;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use TheFoxLab\TflSocial\Contracts\Arrayable;
use TheFoxLab\TflSocial\Traits\ArrayableTrait;
use Traversable;

/**
 * @implements IteratorAggregate<int, BusinessAccount>
 */
final class BusinessAccountCollection implements Arrayable, Countable, IteratorAggregate, JsonSerializable
{
    use ArrayableTrait;

    /**
     * @param list<BusinessAccount> $accounts
     */
    public function __construct(
        private readonly array $accounts = []
    ) {
    }

    /**
     * @return Traversable<int, BusinessAccount>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->accounts);
    }

    public function count(): int
    {
        return count($this->accounts);
    }

    public function first(): ?BusinessAccount
    {
        return $this->accounts[0] ?? null;
    }

    public function find(string $accountId): ?BusinessAccount
    {
        foreach ($this->accounts as $account) {
            if ($account->accountId() === $accountId) {
                return $account;
            }
        }

        return null;
    }

    public function isEmpty(): bool
    {
        return $this->accounts === [];
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
