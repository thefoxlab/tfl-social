<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Config;

use CodeIgniter\Config\BaseConfig;

require_once __DIR__ . '/Constants.php';

class TflSocial extends BaseConfig
{
    public string $accountTable = 'social_account';

    public string $connectionTable = 'social_connection';

    public string $postTable = 'social_post';

    public string $mediaTable = 'social_media';

    public string $syncTable = 'social_sync';

    public string $graphVersion = 'v23.0';

    public string $cachePrefix = 'tfl_social_';

    public array $providers = [
        'facebook' => [
            'enabled' => true,
            'appId' => '',
            'appSecret' => '',
            'redirectUri' => 'social/callback/facebook',
            'permissions' => [
                'business_management','pages_read_engagement',
                'pages_show_list','instagram_manage_insights','instagram_basic'
            ],
        ],
    ];

    public array $http = [
        'baseUrl' => '',
        'timeout' => 30,
        'connectTimeout' => 10,
        'verifySSL' => true,
        'userAgent' => 'TFL-Social/1.0',
    ];

    public array $cache = [
        'enabled' => true,
        'ttl' => 3600,
    ];

    public array $sync = [
        'batchSize' => 50,
        'timeout' => 30,
        'mediaRefreshLimit' => 50,
    ];

    public function __construct()
    {
        if (method_exists(BaseConfig::class, '__construct')) {
            parent::__construct();
        }

        $appId = $this->readEnvironment('facebook.appId');
        if ($appId !== '') {
            $this->providers['facebook']['appId'] = $appId;
        }

        $appSecret = $this->readEnvironment('facebook.appSecret');
        if ($appSecret !== '') {
            $this->providers['facebook']['appSecret'] = $appSecret;
        }
    }

    public static function resolve(?self $config = null): self
    {
        if ($config !== null) {
            return $config;
        }

        if (function_exists('config')) {
            $resolved = config('TflSocial');

            if ($resolved instanceof self) {
                return $resolved;
            }
        }

        if (class_exists('Config\\TflSocial')) {
            /** @var class-string<self> $class */
            $class = 'Config\\TflSocial';
            $resolved = new $class();

            if ($resolved instanceof self) {
                return $resolved;
            }
        }

        return new self();
    }

    private function readEnvironment(string $key): string
    {
        if (! function_exists('env')) {
            return '';
        }

        $value = env($key, '');

        return is_string($value) ? $value : '';
    }
}
