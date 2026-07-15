<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Contracts;

interface SynchronizerInterface
{
    public function account(int $account): self;

    public function connection(int $connection): self;

    public function all(): void;

    public function run(): void;
}
