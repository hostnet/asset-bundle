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
 * @covers \Hostnet\Bundle\AssetBundle\DependencyInjection\Compiler\CollectImportCollectorsPass
 */
class CollectImportCollectorsPassTest extends TestCase
{
    /**
     * @var CollectImportCollectorsPass
     */
    private $collect_import_collectors_pass;

    protected function setUp()
    {
        $this->collect_import_collectors_pass = new CollectImportCollectorsPass();
    }

    public function testProcess()
    {
        $container     = new ContainerBuilder();
        $import_finder = new Definition();

        $container->setDefinition('hostnet_asset.import_finder', $import_finder);
        $container->setDefinition('collector1', (new Definition())->addTag('asset.import_collector'));
        $container->setDefinition('collector2', (new Definition())->addTag('asset.import_collector'));

        $this->collect_import_collectors_pass->process($container);

        self::assertEquals([
            ['addCollector', [new Reference('collector1')]],
            ['addCollector', [new Reference('collector2')]],
        ], $import_finder->getMethodCalls());
    }
}
