<?php
/**
 * @copyright 2017-2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Bundle\AssetBundle\Functional;

use Hostnet\Bundle\AssetBundle\Functional\Fixtures\TestKernel;
use Hostnet\Component\Resolver\Builder\BundlerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @coversNothing
 */
class FunctionalTest extends KernelTestCase
{
    protected function setUp()
    {
        static::bootKernel();
    }

    protected static function getKernelClass()
    {
        return TestKernel::class;
    }

    public function testGetBundler()
    {
        $container = self::$kernel->getContainer();

        $bundler = $container->get('hostnet_asset.bundler');
        self::assertInstanceOf(BundlerInterface::class, $bundler);

        /** @var BundlerInterface $bundler */
        $bundler->bundle($container->get('hostnet_asset.build_config'));
    }
}
