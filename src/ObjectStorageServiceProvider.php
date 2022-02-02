<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

namespace Larva\Flysystem\Tencent;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use Larva\Flysystem\Tencent\Plugins\Cdn;
use Larva\Flysystem\Tencent\Plugins\PutRemoteFile;
use Larva\Flysystem\Tencent\Plugins\PutRemoteFileAs;
use League\Flysystem\Filesystem;
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
     * @throws BindingResolutionException
     */
    public function boot()
    {
        $this->app->make('filesystem')->extend('cos', function ($app, $config) {
            $client = new Client($config);
            $flysystem = new Filesystem(new COSAdapter($client, $config), $config);
            $flysystem->addPlugin(new Cdn());
            $flysystem->addPlugin(new PutRemoteFile());
            $flysystem->addPlugin(new PutRemoteFileAs());
            return $flysystem;
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
