<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Config;

use CodeIgniter\Config\BaseService;
use TheFoxLab\TflSocial\Config\TflSocial as TflSocialConfig;
use TheFoxLab\TflSocial\TflSocial;

class Services extends BaseService
{
    public static function tflSocial(?TflSocialConfig $config = null, bool $getShared = true): TflSocial
    {
        if ($getShared) {
            return static::getSharedInstance('tflSocial', $config);
        }

        $config = TflSocialConfig::resolve($config);

        return new TflSocial($config);
    }
}
