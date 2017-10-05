<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Bundle\AssetBundle\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @covers \Hostnet\Bundle\AssetBundle\DependencyInjection\Compiler\CollectorAssetProcessorsPass
 */
class CollectorAssetProcessorsPassTest extends TestCase
{
    /**
     * @var CollectorAssetProcessorsPass
     */
    private $collector_asset_processors_pass;

    protected function setUp()
    {
        $this->collector_asset_processors_pass = new CollectorAssetProcessorsPass();
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $pipline = new Definition();

        $container->setDefinition('hostnet_asset.pipline', $pipline);
        $container->setDefinition('processor1', (new Definition())->addTag('asset.processor'));
        $container->setDefinition('processor2', (new Definition())->addTag('asset.processor'));

        $this->collector_asset_processors_pass->process($container);

        self::assertEquals([
            ['addProcessor', [new Reference('processor1')]],
            ['addProcessor', [new Reference('processor2')]],
        ], $pipline->getMethodCalls());
    }
}
