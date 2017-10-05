<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Bundle\AssetBundle;

use Hostnet\Bundle\AssetBundle\DependencyInjection\Compiler\CollectImportCollectorsPass;
use Hostnet\Bundle\AssetBundle\DependencyInjection\Compiler\CollectorAssetProcessorsPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \Hostnet\Bundle\AssetBundle\HostnetAssetBundle
 */
class HostnetAssetBundleTest extends TestCase
{
    /**
     * @var HostnetAssetBundle
     */
    private $hostnet_asset_bundle;

    protected function setUp()
    {
        $this->hostnet_asset_bundle = new HostnetAssetBundle();
    }

    private function containsInstanceOf(string $class_name, array $haystack): bool
    {
        foreach ($haystack as $item) {
            if ($item instanceof $class_name) {
                return true;
            }
        }

        return false;
    }

    public function testBuild()
    {
        $container = new ContainerBuilder();

        $this->hostnet_asset_bundle->build($container);

        $passes = $container->getCompilerPassConfig()->getBeforeOptimizationPasses();

        self::assertTrue($this->containsInstanceOf(CollectImportCollectorsPass::class, $passes));
        self::assertTrue($this->containsInstanceOf(CollectorAssetProcessorsPass::class, $passes));
    }
}
