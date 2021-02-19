# laravel-flysystem-cos

This is a Flysystem adapter for the Tencent Cloud COS

[![Build Status](https://travis-ci.com/larvatech/laravel-flysystem-cos.svg?branch=master)](https://travis-ci.com/larvatech/laravel-flysystem-cos)


## Installation

```bash
composer require larva/laravel-flysystem-cos
```

## for Laravel

This service provider must be registered.

```php
// config/app.php

'providers' => [
    '...',
    Larva\Flysystem\Tencent\ObjectStorageServiceProvider::class,
];
```

edit the config file: config/filesystems.php

add config

```php
'cos' => [
    // 'endpoint' => getenv('COS_ENDPOINT'),//接入点，留空即可
    'region' => env('COS_REGION'),
    'credentials' => [
        'appId' => env('COS_APP_ID'),//就是存储桶的后缀 如 1258464748
        'secretId' => env('COS_SECRET_ID'),
        'secretKey' => env('COS_SECRET_KEY'),
        'token' => env('COS_TOKEN'),
    ],
    'bucket' => 'larvatest-1258464748',
    'schema' => 'https',
    'timeout' => 3600,
    'connect_timeout' => 3600,
    'ip' => null,
    'port' => null,
    'domain' => null,
    'proxy' => null,
    'prefix' => getenv('COS_PREFIX'),//前缀
    'encrypt'=> null,
    'url'=>'CDN URL',
    'cdn_key' => 'izDMqzld6U4AFQjg',
    'cdn_sign_type' => 'D'//A/B/C/D
],
```

change default to oss

```php
    'default' => 'cos'
```

## Use

see [Laravel wiki](https://laravel.com/docs/6.0/filesystem)
