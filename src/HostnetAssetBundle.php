<?php
declare(strict_types = 1);
/**
 * @copyright 2017 Hostnet B.V.
 */
namespace Hostnet\Bundle\AssetBundle;

use Hostnet\Bundle\AssetBundle\DependencyInjection\Compiler\CollectImportCollectorsPass;
use Hostnet\Bundle\AssetBundle\DependencyInjection\Compiler\CollectorAssetProcessorsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HostnetAssetBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CollectImportCollectorsPass());
        $container->addCompilerPass(new CollectorAssetProcessorsPass());
    }
}
