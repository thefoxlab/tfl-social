<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Services;

use CodeIgniter\Entity\Entity;
use TheFoxLab\TflSocial\Entities\Account;
use TheFoxLab\TflSocial\Exceptions\RepositoryException;
use TheFoxLab\TflSocial\Repositories\AccountRepository;

use function trim;

final class AccountService
{
    public function __construct(
        private readonly AccountRepository $accounts = new AccountRepository()
    ) {
    }

    /**
     * @param array<string, mixed>|Account $data
     */
    public function createAccount(array|Account $data): Account
    {
        return $this->account($this->accounts->insert($data));
    }

    /**
     * @param array<string, mixed>|Account $data
     */
    public function updateAccount(int|string $accountId, array|Account $data): Account
    {
        return $this->account($this->accounts->update($accountId, $data));
    }

    public function deleteAccount(int|string $accountId): void
    {
        $this->accounts->delete($accountId);
    }

    public function getAccount(int|string $accountId): ?Account
    {
        $account = $this->accounts->findById($accountId);

        return $account === null ? null : $this->account($account);
    }

    public function findOrCreateByName(string $name): Account
    {
        $name = trim($name);

        if ($name === '') {
            throw new RepositoryException('Account name cannot be empty.');
        }

        $account = $this->accounts->findByName($name);

        if ($account !== null) {
            return $this->account($account);
        }

        return $this->createAccount([
            'name' => $name,
            'status' => Account::STATUS_ACTIVE,
        ]);
    }

    private function account(Entity $entity): Account
    {
        if (! $entity instanceof Account) {
            throw new RepositoryException('Account repository returned an invalid entity.');
        }

        return $entity;
    }
}
