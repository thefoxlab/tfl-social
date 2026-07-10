<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Repositories;

use TheFoxLab\TflSocial\Models\AccountModel;

final class AccountRepository extends AbstractRepository
{
    public function __construct(?AccountModel $model = null)
    {
        parent::__construct($model ?? new AccountModel(), 'social_account_id');
    }
}
