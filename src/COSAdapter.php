<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

namespace Larva\Flysystem\Tencent;

use Carbon\Carbon;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\CanOverwriteFiles;
use League\Flysystem\Adapter\Polyfill\StreamedTrait;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;
use Qcloud\Cos\Client;
use Qcloud\Cos\Exception\ServiceResponseException;

/**
 * COS V5 适配器
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class COSAdapter extends AbstractAdapter implements CanOverwriteFiles
{
    use StreamedTrait;

    /**
     * @var Client
     */
    protected Client $client;

    /**
     * @var array
     */
    protected array $config = [];

    /**
     * Adapter constructor.
     *
     * @param Client $client
     * @param array $config
     */
    public function __construct(Client $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
        if (isset($config['prefix'])) {
            $this->setPathPrefix($config['prefix']);
        }
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        $object = $this->applyPathPrefix($path);
        $options = $this->prepareUploadConfig($config);
        if (!isset($options['length'])) {
            $options['length'] = Util::contentSize($contents);
        }
        if (!isset($options['Content-Type'])) {
            $options['Content-Type'] = Util::guessMimeType($path, $contents);
        }
        try {
            $this->client->upload($this->getBucket(), $object, $contents, $this->prepareUploadConfig($config));
            $type = 'file';
            $result = compact('type', 'path', 'contents');
            $result['mimetype'] = $options['Content-Type'];
            $result['size'] = $options['length'];
            return $result;
        } catch (ServiceResponseException $e) {
            return false;
        }
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     * @return bool
     */
    public function rename($path, $newpath): bool
    {
        if (!$this->copy($path, $newpath)) {
            return false;
        }
        return $this->delete($path);
    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     * @return bool
     */
    public function copy($path, $newpath): bool
    {
        $object = $this->applyPathPrefix($path);
        $newObject = $this->applyPathPrefix($newpath);
        try {
            $result = $this->client->headObject([
                'Bucket' => $this->getBucket(),
                'Key' => $object,
            ]);
            $this->client->copyObject([
                'Bucket' => $this->getBucket(),
                'Key' => $newObject,
                'CopySource' => $result['Location'],
            ]);
        } catch (ServiceResponseException $e) {
            return false;
        }
        return true;
    }

    /**
     * Delete a file.
     *
     * @param string $path
     * @return bool
     */
    public function delete($path): bool
    {
        $object = $this->applyPathPrefix($path);
        try {
            $this->client->deleteObject([
                'Bucket' => $this->getBucket(),
                'Key' => $object,
            ]);
        } catch (ServiceResponseException $e) {
            return false;
        }
        return true;
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     * @return bool
     */
    public function deleteDir($dirname): bool
    {
        $object = $this->applyPathPrefix($dirname);
        try {
            return (bool)$this->client->deleteObject([
                'Bucket' => $this->getBucket(),
                'Key' => $object . '/',
            ]);
        } catch (ServiceResponseException $e) {
            return false;
        }
    }

    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     * @return array|false
     */
    public function createDir($dirname, Config $config)
    {
        $dir = $this->applyPathPrefix($dirname);
        try {
            $this->client->putObject([
                'Bucket' => $this->getBucket(),
                'Key' => $dir . '/',
                'Body' => '',
            ]);
        } catch (ServiceResponseException $e) {
            return false;
        }
        return ['path' => $dirname, 'type' => 'dir'];
    }

    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     * @return array|false file meta data
     */
    public function setVisibility($path, $visibility)
    {
        $location = $this->applyPathPrefix($path);
        try {
            $this->client->putObjectAcl([
                'Bucket' => $this->getBucket(),
                'Key' => $location,
                'ACL' => $this->normalizeVisibility($visibility),
            ]);
        } catch (ServiceResponseException $e) {
            return false;
        }
        return $this->getMetadata($path);
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     * @return bool
     */
    public function has($path): bool
    {
        $object = $this->applyPathPrefix($path);
        try {
            $this->client->headObject([
                'Bucket' => $this->getBucket(),
                'Key' => $object,
            ]);
            return true;
        } catch (ServiceResponseException $e) {
            return false;
        }
    }

    /**
     * Read a file.
     *
     * @param string $path
     * @return array|false
     */
    public function read($path)
    {
        $object = $this->applyPathPrefix($path);
        try {
            $response = $this->client->getObject([
                'Bucket' => $this->getBucket(),
                'Key' => $object
            ]);
            $contents = (string)$response['Body'];
        } catch (ServiceResponseException $e) {
            return false;
        }
        return compact('contents', 'path');
    }

    /**
     * 获取对象访问Url
     * @param string $path
     * @return string
     */
    public function getUrl($path): string
    {
        $location = $this->applyPathPrefix($path);
        if (isset($this->config['url']) && !empty($this->config['url'])) {
            $url = $this->config['url'] . '/' . ltrim($location, '/');
            if (isset($this->config['cdn_key']) && !empty($this->config['cdn_key'])) {
                $url = $this->cdn()->signature($url);
            }
            return $url;
        } else {
            $visibility = $this->getVisibility($path);
            if ($visibility && $visibility['visibility'] == 'private') {
                return $this->getTemporaryUrl($path, Carbon::now()->addMinutes(5), []);
            }
            $options = ['Scheme' => $this->config['scheme'] ?? 'http'];
            return $this->client->getObjectUrl($this->getBucket(), $location, 0, $options);
        }
    }

    /**
     * 获取文件临时访问路径
     * @param string $path
     * @param \DateTimeInterface $expiration
     * @param array $options
     * @return string
     */
    public function getTemporaryUrl($path, \DateTimeInterface $expiration, array $options = []): string
    {
        $location = $this->applyPathPrefix($path);
        $options = array_merge(
            $options,
            ['Scheme' => $this->config['scheme'] ?? 'http']
        );
        return $this->client->getObjectUrl($this->getBucket(), $location, $expiration, $options);
    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool $recursive
     * @return array
     */
    public function listContents($directory = '', $recursive = false): array
    {
        $directory = $this->applyPathPrefix($directory);
        $list = [];
        $marker = '';
        while (true) {
            $response = $this->listObjects($directory, $recursive, $marker);
            foreach ((array)$response['Contents'] as $content) {
                $list[] = $this->normalizeFileInfo($content);
            }
            if (!$response['IsTruncated']) {
                break;
            }
            $marker = $response['NextMarker'] ?: '';
        }
        return $list;
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     * @return array|false
     */
    public function getMetadata($path)
    {
        $object = $this->applyPathPrefix($path);
        try {
            $result = $this->client->headObject([
                'Bucket' => $this->getBucket(),
                'Key' => $object,
            ]);
        } catch (ServiceResponseException $e) {
            return false;
        }
        return [
            'type' => 'file',
            'dirname' => Util::dirname($path),
            'path' => $path,
            'timestamp' => strtotime($result['LastModified']),
            'mimetype' => $result['ContentType'],
            'size' => $result['ContentLength'],
        ];
    }

    /**
     * Get the size of a file.
     *
     * @param string $path
     * @return array|false
     */
    public function getSize($path)
    {
        $meta = $this->getMetadata($path);
        return isset($meta['size'])
            ? ['size' => $meta['size']] : false;
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     * @return array|false
     */
    public function getMimetype($path)
    {
        $meta = $this->getMetadata($path);
        return isset($meta['mimetype'])
            ? ['mimetype' => $meta['mimetype']] : false;
    }

    /**
     * Get the last modified time of a file as a timestamp.
     *
     * @param string $path
     * @return array|false
     */
    public function getTimestamp($path)
    {
        $meta = $this->getMetadata($path);
        return isset($meta['timestamp'])
            ? ['timestamp' => strtotime($meta['timestamp'])] : false;
    }

    /**
     * Get the visibility of a file.
     *
     * @param string $path
     * @return array|false
     */
    public function getVisibility($path)
    {
        $location = $this->applyPathPrefix($path);
        try {
            $response = $this->client->getObjectAcl([
                'Bucket' => $this->getBucket(),
                'Key' => $location,
            ]);
            foreach ($response['Grants'] as $grant) {
                if (isset($grant['Grantee']['URI'])
                    && $grant['Permission'] === 'READ'
                    && strpos($grant['Grantee']['URI'], 'global/AllUsers') !== false
                ) {
                    return ['visibility' => AdapterInterface::VISIBILITY_PUBLIC];
                }
            }

            return ['visibility' => AdapterInterface::VISIBILITY_PRIVATE];
        } catch (ServiceResponseException $e) {
            return false;
        }
    }

    /**
     * List objects of a directory.
     * @param string $directory
     * @param bool $recursive
     * @param string $marker max return 1000 record, if record greater than 1000
     *                          you should set the next marker to get the full list
     *
     * @return array
     */
    private function listObjects(string $directory = '', bool $recursive = false, $marker = ''): array
    {
        try {
            return $this->client->listObjects([
                'Bucket' => $this->getBucket(),
                'Prefix' => ($directory === '') ? '' : ($directory . '/'),
                'Delimiter' => $recursive ? '' : '/',
                'Marker' => $marker,
                'MaxKeys' => 1000,
            ]);
        } catch (ServiceResponseException $e) {
            return [
                'Contents' => [],
                'IsTruncated' => false,
                'NextMarker' => '',
            ];
        }
    }

    /**
     * @param Config $config
     *
     * @return array
     */
    private function prepareUploadConfig(Config $config): array
    {
        $options = [];

        if (isset($this->config['encrypt']) && $this->config['encrypt']) {
            $options['params']['ServerSideEncryption'] = 'AES256';
        }

        if ($config->has('params')) {
            $options['params'] = $config->get('params');
        }

        if ($config->has('visibility')) {
            $options['params']['ACL'] = $this->normalizeVisibility($config->get('visibility'));
        }

        return $options;
    }

    /**
     * @param $visibility
     *
     * @return string
     */
    private function normalizeVisibility($visibility): string
    {
        switch ($visibility) {
            case AdapterInterface::VISIBILITY_PUBLIC:
                $visibility = 'public-read';
                break;
        }

        return $visibility;
    }

    /**
     * @param array $content
     *
     * @return array
     */
    private function normalizeFileInfo(array $content): array
    {
        $path = pathinfo($content['Key']);

        return [
            'type' => substr($content['Key'], -1) === '/' ? 'dir' : 'file',
            'path' => $content['Key'],
            'timestamp' => Carbon::parse($content['LastModified'])->getTimestamp(),
            'size' => (int)$content['Size'],
            'dirname' => $path['dirname'] === '.' ? '' : (string)$path['dirname'],
            'basename' => (string)$path['basename'],
            'extension' => isset($path['extension']) ? $path['extension'] : '',
            'filename' => (string)$path['filename'],
        ];
    }

    /**
     * Get the COS Client bucket.
     *
     * @return string
     */
    public function getBucket(): string
    {
        return $this->config['bucket'];
    }

    /**
     * Get the COS Client instance.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Get the COS Client region.
     *
     * @return string
     */
    public function getRegion(): string
    {
        return \Qcloud\Cos\region_map($this->config['region']);
    }
}
