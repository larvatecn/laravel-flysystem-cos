<?php

namespace Larva\Flysystem\Tencent\Tests;

use Larva\Flysystem\Tencent\COSAdapter;
use Larva\Flysystem\Tencent\Plugins\Cdn;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Qcloud\Cos\Client;

class CDNSignatureTest extends TestCase
{
    public function Provider()
    {
        $config = [
            // 'endpoint' => getenv('COS_ENDPOINT'),
            'region' => getenv('COS_REGION'),
            'credentials' => [
                'appId' => getenv('COS_APP_ID'),
                'secretId' => getenv('COS_SECRET_ID'),
                'secretKey' => getenv('COS_SECRET_KEY'),
                'token' => getenv('COS_TOKEN'),
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
            'cdn_key' => 'izDMqzld6U4AFQjg',
            'cdn_sign_type' => 'D'//A/B/C/D
        ];

        $client = new Client($config);
        $adapter = new COSAdapter($client, $config);
        $filesystem = new Filesystem($adapter, $config);
        $filesystem->addPlugin(new Cdn());
        return [
            [$filesystem],
        ];
    }

    /**
     * @dataProvider Provider
     */
    public function testSignature(Filesystem $filesystem)
    {
        $this->assertSame(
            'http://www.test.com/1.mp4?sign=998883560007376ea1c3feea6fdba557&t=5e6ce1c7',
            $filesystem->cdn()->signature('http://www.test.com/1.mp4', null, 1584193991, 123)
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testSignatureA(Filesystem $filesystem)
    {
        $this->assertSame(
            'http://www.test.com/1.mp4?sign=1584193991-123456123123-0-3b2ddbfee5227ebc520a90fa6182df3c',
            $filesystem->cdn()->signatureA('http://www.test.com/1.mp4', null, 1584193991, '123456123123')
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testSignatureB(Filesystem $filesystem)
    {
        date_default_timezone_set('UTC');

        $this->assertSame(
            'http://www.test.com/202003141353/9042ff8303f5ef81a4211d8abbb51c64/1.mp4',
            $filesystem->cdn()->signatureB('http://www.test.com/1.mp4', null, 1584193991)
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testSignatureC(Filesystem $filesystem)
    {
        $this->assertSame(
            'http://www.test.com/998883560007376ea1c3feea6fdba557/5e6ce1c7/1.mp4',
            $filesystem->cdn()->signatureC('http://www.test.com/1.mp4', null, 1584193991)
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testSignatureD(Filesystem $filesystem)
    {
        $this->assertSame(
            'http://www.test.com/1.mp4?sign=998883560007376ea1c3feea6fdba557&t=5e6ce1c7',
            $filesystem->cdn()->signatureD('http://www.test.com/1.mp4', null, 1584193991)
        );
    }
}
