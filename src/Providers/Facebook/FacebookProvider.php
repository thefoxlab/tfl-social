<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Facebook;

use TheFoxLab\TflSocial\Providers\AbstractProvider;

final class FacebookProvider extends AbstractProvider
{
    public function __construct()
    {
        parent::__construct('facebook');
    }
}
