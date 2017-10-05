<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Bundle\AssetBundle\DependencyInjection;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Bundle\AssetBundle\DependencyInjection\ArrayConfig
 */
class ArrayConfigTest extends TestCase
{
    public function testGeneric()
    {
        $config = new ArrayConfig(
            true,
            __DIR__,
            ['foo'],
            ['bar'],
            'phpunit',
            'web',
            'src',
            'var'
        );

        self::assertEquals(true, $config->isDev());
        self::assertEquals(__DIR__, $config->cwd());
        self::assertEquals(['foo'], $config->getEntryPoints());
        self::assertEquals(['bar'], $config->getAssetFiles());
        self::assertEquals('phpunit', $config->getOutputFolder());
        self::assertEquals('web', $config->getWebRoot());
        self::assertEquals('src', $config->getSourceRoot());
        self::assertEquals('var', $config->getCacheDir());
    }
}
