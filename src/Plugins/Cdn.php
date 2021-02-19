<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 * @license http://www.larva.com.cn/license/
 */

namespace Larva\Flysystem\Tencent\Plugins;

use League\Flysystem\Plugin\AbstractPlugin;

/**
 * Class Cdn
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class Cdn extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'cdn';
    }

    /**
     * @return $this
     */
    public function handle()
    {
        return $this;
    }

    /**
     * @param string $url
     * @param string $key
     * @param int $timestamp
     * @param string $random
     * @param string $signName
     * @param string $timeName
     *
     * @return string
     */
    public function signature($url, $key = null, $timestamp = null, $random = null, $signName = 'sign', $timeName = 't')
    {
        switch ($this->filesystem->getConfig()->get('cdn_sign_type')) {
            case 'A':
                return $this->signatureA($url, $key, $timestamp, $random, $signName);
                break;
            case 'B':
                return $this->signatureB($url, $key, $timestamp);
                break;
            case 'C':
                return $this->signatureC($url, $key, $timestamp);
                break;
            case 'D':
            default:
                return $this->signatureD($url, $key, $timestamp, $signName, $timeName);
        }
    }

    /**
     * @param string $url
     * @param string $key
     * @param int $timestamp
     * @param string $random
     * @param string $signName
     *
     * @return string
     */
    public function signatureA($url, $key = null, $timestamp = null, $random = null, $signName = 'sign')
    {
        $key = $key ?: $this->filesystem->getConfig()->get('cdn_key');
        $timestamp = $timestamp ?: time();
        $random = $random ?: sha1(uniqid('', true));

        $parsed = parse_url($url);
        $hash = md5(sprintf('%s-%s-%s-%s-%s', $parsed['path'], $timestamp, $random, 0, $key));
        $signature = sprintf('%s-%s-%s-%s', $timestamp, $random, 0, $hash);
        $query = http_build_query([$signName => $signature]);
        $separator = empty($parsed['query']) ? '?' : '&';
        return $url . $separator . $query;
    }

    /**
     * @param string $url
     * @param string $key
     * @param int $timestamp
     *
     * @return string
     */
    public function signatureB($url, $key = null, $timestamp = null)
    {
        $key = $key ?: $this->filesystem->getConfig()->get('cdn_key');
        $timestamp = date('YmdHi', $timestamp ?: time());

        $parsed = parse_url($url);
        $hash = md5($key . $timestamp . $parsed['path']);

        return sprintf(
            '%s://%s/%s/%s%s',
            $parsed['scheme'], $parsed['host'], $timestamp, $hash, $parsed['path']
        );
    }

    /**
     * @param string $url
     * @param string $key
     * @param int $timestamp
     *
     * @return string
     */
    public function signatureC($url, $key = null, $timestamp = null)
    {
        $key = $key ?: $this->filesystem->getConfig()->get('cdn_key');
        $timestamp = dechex($timestamp ?: time());

        $parsed = parse_url($url);
        $hash = md5($key . $parsed['path'] . $timestamp);

        return sprintf(
            '%s://%s/%s/%s%s',
            $parsed['scheme'], $parsed['host'], $hash, $timestamp, $parsed['path']
        );
    }

    /**
     * @param string $url
     * @param string $key
     * @param int $timestamp
     * @param string $signName
     * @param string $timeName
     *
     * @return string
     */
    public function signatureD($url, $key = null, $timestamp = null, $signName = 'sign', $timeName = 't')
    {
        $key = $key ?: $this->filesystem->getConfig()->get('cdn_key');
        $timestamp = dechex($timestamp ?: time());

        $parsed = parse_url($url);
        $signature = md5($key . $parsed['path'] . $timestamp);
        $query = http_build_query([$signName => $signature, $timeName => $timestamp]);
        $separator = empty($parsed['query']) ? '?' : '&';

        return $url . $separator . $query;
    }
}
