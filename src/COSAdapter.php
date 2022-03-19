<?php

namespace Larva\Flysystem\Tencent;

use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use Qcloud\Cos\Client;

/**
 * COS V5 适配器
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class COSAdapter extends FilesystemAdapter
{
    /**
     * The COS client.
     *
     * @var Client
     */
    protected Client $client;

    /**
     * Create a new AwsS3V3FilesystemAdapter instance.
     *
     * @param FilesystemOperator $driver
     * @param TencentCOSAdapter $adapter
     * @param array $config
     * @param Client $client
     */
    public function __construct(FilesystemOperator $driver, TencentCOSAdapter $adapter, array $config, Client $client)
    {
        parent::__construct($driver, $adapter, $config);
        $this->client = $client;
    }

    /**
     * Get the URL for the file at the given path.
     *
     * @param string $path
     * @return string
     */
    public function url($path): string
    {
        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
        if (isset($this->config['url'])) {
            return $this->concatPathToUrl($this->config['url'], $this->prefixer->prefixPath($path));
        }
        $visibility = $this->getVisibility($path);
        if ($visibility == FilesystemContract::VISIBILITY_PRIVATE) {
            return $this->temporaryUrl($path, Carbon::now()->addMinutes(5), []);
        } else {
            $options = ['Scheme' => $this->config['scheme'] ?? 'http'];
            return $this->client->getObjectUrl($this->config['bucket'], $this->prefixer->prefixPath($path), 0, $options);
        }
    }

    /**
     * Get a temporary URL for the file at the given path.
     *
     * @param string $path
     * @param \DateTimeInterface $expiration
     * @param array $options
     * @return string
     */
    public function temporaryUrl($path, $expiration, array $options = []): string
    {
        $location = $this->prefixer->prefixPath($path);
        $options = array_merge($options, ['Scheme' => $this->config['scheme'] ?? 'http']);
        return $this->client->getObjectUrl($this->config['bucket'], $location, $expiration, $options);
    }

    /**
     * Get the underlying COS client.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }
}
