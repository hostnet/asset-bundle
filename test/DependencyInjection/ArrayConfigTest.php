<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Bundle\AssetBundle\DependencyInjection;

use Hostnet\Component\Resolver\Bundler\Runner\SingleProcessRunner;
use Hostnet\Component\Resolver\Bundler\Runner\UnixSocketRunner;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Hostnet\Component\Resolver\Plugin\PluginInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \Hostnet\Bundle\AssetBundle\DependencyInjection\ArrayConfig
 */
class ArrayConfigTest extends TestCase
{
    public function testGeneric()
    {
        $plugins = [$this->prophesize(PluginInterface::class)->reveal()];
        $nodejs  = new Executable('a', 'b');
        $config  = new ArrayConfig(
            true,
            __DIR__,
            ['phpunit'],
            ['foo'],
            ['bar'],
            'web',
            'phpunit',
            'src',
            'var',
            true,
            $plugins,
            $nodejs
        );

        self::assertEquals(true, $config->isDev());
        self::assertEquals(__DIR__, $config->getProjectRoot());
        self::assertEquals(['phpunit'], $config->getIncludePaths());
        self::assertEquals(['foo'], $config->getEntryPoints());
        self::assertEquals(['bar'], $config->getAssetFiles());
        self::assertEquals('web/phpunit', $config->getOutputFolder());
        self::assertEquals('phpunit', $config->getOutputFolder(false));
        self::assertEquals('src', $config->getSourceRoot());
        self::assertEquals('var', $config->getCacheDir());
        self::assertInstanceOf(UnixSocketRunner::class, $config->getRunner());
        self::assertSame($plugins, $config->getPlugins());
        self::assertSame($nodejs, $config->getNodeJsExecutable());
        self::assertInstanceOf(NullLogger::class, $config->getLogger());
        self::assertInstanceOf(EventDispatcherInterface::class, $config->getEventDispatcher());

        $dispatcher = new EventDispatcher();
        $logger     = new NullLogger();
        $config     = new ArrayConfig(
            true,
            __DIR__,
            ['phpunit'],
            ['foo'],
            ['bar'],
            'web',
            'phpunit',
            'src',
            'var',
            false,
            $plugins,
            $nodejs,
            $dispatcher,
            $logger
        );

        self::assertInstanceOf(SingleProcessRunner::class, $config->getRunner());
        self::assertSame($logger, $config->getLogger());
        self::assertSame($dispatcher, $config->getEventDispatcher());
    }
}
