<?php

namespace Larva\Flysystem\Tencent;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Visibility;
use Qcloud\Cos\Client;

/**
 * 腾讯云对象存储服务提供者
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class ObjectStorageServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function boot()
    {
        $this->app->make('filesystem')->extend('cos', function ($app, $config) {
            $root = (string)($config['root'] ?? '');
            $config['directory_separator'] = '/';
            $visibility = new PortableVisibilityConverter($config['visibility'] ?? Visibility::PUBLIC);
            $client = new Client($config);
            $adapter = new TencentCOSAdapter($client, $config['bucket'], $root, $visibility, null, $config['options'] ?? []);

            return new COSAdapter(
                new Flysystem($adapter, Arr::only($config, [
                    'directory_visibility',
                    'disable_asserts',
                    'temporary_url',
                    'url',
                    'visibility',
                ])), $adapter, $config, $client
            );
        });
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
