<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Bundle\AssetBundle;

use Hostnet\Bundle\AssetBundle\DependencyInjection\Compiler\CollectImportCollectorsPass;
use Hostnet\Bundle\AssetBundle\DependencyInjection\Compiler\CollectorAssetProcessorsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class HostnetAssetBundle extends Bundle
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
