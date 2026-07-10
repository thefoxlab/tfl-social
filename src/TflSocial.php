<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial;

final class TflSocial
{
    private ?FeedBuilder $feedBuilder = null;

    private ?Connector $connector = null;

    private ?Synchronizer $synchronizer = null;

    private ?ProviderManager $providerManager = null;

    public function feed(): FeedBuilder
    {
        return $this->feedBuilder ??= new FeedBuilder();
    }

    public function connect(): Connector
    {
        return $this->connector ??= new Connector();
    }

    public function sync(): Synchronizer
    {
        return $this->synchronizer ??= new Synchronizer();
    }

    public function providers(): ProviderManager
    {
        return $this->providerManager ??= new ProviderManager();
    }
}
