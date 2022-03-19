# laravel-flysystem-cos

<p align="center">
    <a href="https://packagist.org/packages/larva/laravel-flysystem-cos"><img src="https://poser.pugx.org/larva/laravel-flysystem-cos/v/stable" alt="Stable Version"></a>
    <a href="https://packagist.org/packages/larva/laravel-flysystem-cos"><img src="https://poser.pugx.org/larva/laravel-flysystem-cos/downloads" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/larva/laravel-flysystem-cos"><img src="https://poser.pugx.org/larva/laravel-flysystem-cos/license" alt="License"></a>
</p>

适用于 Laravel 的腾讯云 COS 适配器，完整支持腾讯云 COS 所有方法和操作。

## 安装

```bash
composer require larva/laravel-flysystem-cos -vv
```

修改配置文件: `config/filesystems.php`

添加一个磁盘配置

```php
'cos' => [
    'driver' => 'cos',
    // 'endpoint' => getenv('TENCENT_COS_ENDPOINT'),//接入点，留空即可
    'region' => env('TENCENT_COS_REGION'),
    'credentials' => [//认证凭证
        'appId' => env('TENCENT_COS_APP_ID'),//就是存储桶的后缀 如 1258464748
        'secretId' => env('TENCENT_COS_SECRET_ID'),
        'secretKey' => env('TENCENT_COS_SECRET_KEY'),
        'token' => env('TENCENT_COS_TOKEN'),
    ],
    'bucket' => env('TENCENT_COS_BUCKET'),
    'root' => getenv('COS_PREFIX'),//前缀
    'url'=> env('TENCENT_COS_URL'),//CDN URL 
    //以下均可省略
    'schema' => 'https',
    'timeout' => 3600,
    'connect_timeout' => 3600,
    'ip' => null,
    'port' => null,
    'domain' => null,
    'proxy' => null,
    
    'encrypt'=> null,
    
],
```

修改默认存储驱动

```php
    'default' => 'cos'
```

## 使用方法

参见 [Laravel wiki](https://laravel.com/docs/9.x/filesystem)
