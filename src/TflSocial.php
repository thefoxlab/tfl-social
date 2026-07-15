<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial;

use TheFoxLab\TflSocial\Entities\Account;
use TheFoxLab\TflSocial\Services\AccountService;

final class TflSocial
{
    private ?FeedBuilder $feedBuilder = null;

    private ?Connector $connector = null;

    private ?Synchronizer $synchronizer = null;

    private ?ProviderManager $providerManager = null;

    private ?Account $account = null;

    private ?AccountService $accountService = null;

    public function account(string $name): self
    {
        $this->account = $this->accountService()->findOrCreateByName($name);

        if ($this->connector !== null) {
            $this->connector->account($this->account);
        }

        if ($this->synchronizer !== null) {
            $accountId = $this->account->social_account_id;

            if (is_int($accountId) || is_string($accountId)) {
                $this->synchronizer->account((int) $accountId);
            }
        }

        return $this;
    }

    public function feed(): FeedBuilder
    {
        return $this->feedBuilder ??= new FeedBuilder();
    }

    public function connect(): Connector
    {
        if ($this->connector === null) {
            $this->connector = new Connector(accountService: $this->accountService());
        }

        if ($this->account !== null) {
            $this->connector->account($this->account);
        }

        return $this->connector;
    }

    public function sync(): Synchronizer
    {
        $this->synchronizer ??= new Synchronizer();

        if ($this->account !== null) {
            $accountId = $this->account->social_account_id;

            if (is_int($accountId) || is_string($accountId)) {
                $this->synchronizer->account((int) $accountId);
            }
        }

        return $this->synchronizer;
    }

    public function providers(): ProviderManager
    {
        return $this->providerManager ??= new ProviderManager();
    }

    public function facebook(): Connector
    {
        return $this->connect()->provider('facebook');
    }

    public function instagram(): Connector
    {
        return $this->connect()->provider('facebook');
    }

    private function accountService(): AccountService
    {
        return $this->accountService ??= new AccountService();
    }
}
