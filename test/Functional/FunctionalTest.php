<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Bundle\AssetBundle\Functional;

use Hostnet\Bundle\AssetBundle\Functional\Fixtures\TestKernel;
use Hostnet\Component\Resolver\Bundler\PipelineBundler;
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

    public function testGetPipeline()
    {
        $container = self::$kernel->getContainer();
        self::assertInstanceOf(
            PipelineBundler::class,
            $container->get('hostnet_asset.bundler')
        );
    }
}
