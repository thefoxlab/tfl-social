<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Instagram;

use TheFoxLab\TflSocial\Providers\AbstractProvider;

final class InstagramProvider extends AbstractProvider
{
    public function __construct()
    {
        parent::__construct('instagram');
    }
}
