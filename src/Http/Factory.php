<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Http;

use TheFoxLab\TflSocial\Config\TflSocial;

final class Factory
{
    public static function create(?TflSocial $config = null): ClientInterface
    {
        return new Client($config ?? new TflSocial());
    }
}
