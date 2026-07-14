<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Repositories;

use CodeIgniter\Entity\Entity;
use TheFoxLab\TflSocial\Models\AccountModel;

final class AccountRepository extends AbstractRepository
{
    public function __construct(?AccountModel $model = null)
    {
        parent::__construct($model ?? new AccountModel(), 'social_account_id');
    }

    public function findByName(string $name): ?Entity
    {
        return $this->findOne([
            'name' => $name,
        ]);
    }
}
