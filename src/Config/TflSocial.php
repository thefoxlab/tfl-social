<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Config;

use CodeIgniter\Config\BaseConfig;

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
            'redirectUri' => '/social/callback/facebook',
            'permissions' => [
                'pages_show_list',
                'pages_read_engagement',
                'pages_read_user_content',
                'business_management',
            ],
        ],
        'instagram' => [
            'enabled' => true,
            'appId' => '',
            'appSecret' => '',
            'redirectUri' => '/social/callback/instagram',
            'permissions' => [
                'instagram_basic',
                'instagram_manage_insights',
                'pages_show_list',
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
    ];

    public function __construct()
    {
        if (method_exists(BaseConfig::class, '__construct')) {
            parent::__construct();
        }

        $this->providers['facebook']['appId'] = $this->readEnvironment('facebook.appId');
        $this->providers['facebook']['appSecret'] = $this->readEnvironment('facebook.appSecret');
        $this->providers['instagram']['appId'] = $this->readEnvironment('instagram.appId');
        $this->providers['instagram']['appSecret'] = $this->readEnvironment('instagram.appSecret');
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
