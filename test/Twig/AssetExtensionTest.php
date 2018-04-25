<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Bundle\AssetBundle\Twig;

use Hostnet\Component\Resolver\Config\ConfigInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Bundle\AssetBundle\Twig\AssetExtension
 */
class AssetExtensionTest extends TestCase
{
    private $config;

    /**
     * @var AssetExtension
     */
    private $asset_extension;

    protected function setUp()
    {
        $this->config = $this->prophesize(ConfigInterface::class);

        $this->asset_extension = new AssetExtension($this->config->reveal());
    }

    public function testGetFunctions()
    {
        self::assertEquals([
            new \Twig_SimpleFunction('asset_url', [$this->asset_extension, 'assetUrl']),
        ], $this->asset_extension->getFunctions());
    }

    public function testAssetUrl()
    {
        $this->config->getOutputFolder(false)->willReturn('phpunit');
        $this->config->getOutputFolder()->willReturn(basename(__DIR__));
        $this->config->getProjectRoot()->willReturn(dirname(__DIR__));

        self::assertRegExp(
            '~^/phpunit/AssetExtensionTest\.php\?[0-9]+$~',
            $this->asset_extension->assetUrl(basename(__FILE__))
        );
    }
}
