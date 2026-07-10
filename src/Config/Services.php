<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Config;

use CodeIgniter\Config\BaseService;
use TheFoxLab\TflSocial\TflSocial;

class Services extends BaseService
{
    public static function tflSocial(bool $getShared = true): TflSocial
    {
        if ($getShared) {
            return static::getSharedInstance('tflSocial');
        }

        return new TflSocial();
    }
}
