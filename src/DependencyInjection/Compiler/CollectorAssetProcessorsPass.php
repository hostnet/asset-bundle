<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Bundle\AssetBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class CollectorAssetProcessorsPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('hostnet_asset.pipline');

        foreach ($container->findTaggedServiceIds('asset.processor') as $id => $tags) {
            $definition->addMethodCall('addProcessor', [new Reference($id)]);
        }
    }
}
