<?php
/**
 * @copyright 2017-present Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Bundle\AssetBundle\DependencyInjection;

use Hostnet\Bundle\AssetBundle\Command\CompileCommand;
use Hostnet\Bundle\AssetBundle\Command\DebugCommand;
use Hostnet\Bundle\AssetBundle\EventListener\AssetsChangeListener;
use Hostnet\Bundle\AssetBundle\Twig\AssetExtension;
use Hostnet\Component\Resolver\Builder\BuildConfig;
use Hostnet\Component\Resolver\Builder\Bundler;
use Hostnet\Component\Resolver\Cache\Cache;
use Hostnet\Component\Resolver\Config\SimpleConfig;
use Hostnet\Component\Resolver\Import\ImportFinder;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Hostnet\Component\Resolver\Plugin\PluginActivator;
use Hostnet\Component\Resolver\Plugin\PluginApi;
use Hostnet\Component\Resolver\Plugin\PluginInterface;
use Hostnet\Component\Resolver\Split\OneOnOneSplittingStrategy;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\KernelEvents;

final class HostnetAssetExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        // Create the node information
        $node_executable = new Definition(Executable::class, [
            $config['bin']['node'],
            $config['bin']['node_modules'],
        ]);
        $node_executable->setPublic(false);

        $container->setDefinition('hostnet_asset.node.executable', $node_executable);

        $plugins = [];
        foreach ($config['plugins'] as $name => $is_enabled) {
            if (false === $is_enabled) {
                continue;
            }

            if (false === is_subclass_of($name, PluginInterface::class)) {
                throw new InvalidConfigurationException(sprintf(
                    'Class %s should implement %s.',
                    $name,
                    PluginInterface::class
                ));
            }

            $plugins[] = $this->configurePlugin($name, $container);
        }

        $cache_dir = $container->getParameter('kernel.cache_dir') . '/assets';

        $split_strategy = new Definition(
            OneOnOneSplittingStrategy::class,
            [$config['source_root'], $config['excluded_files']]
        );
        $split_strategy->setPublic(false);

        $container->setDefinition('hostnet_asset.split_strategy', $split_strategy);
        // Create config
        $bundler_config = (new Definition(SimpleConfig::class, [
            $container->getParameter('kernel.debug'),
            $container->getParameter('kernel.project_dir'),
            $config['include_paths'],
            $config['files'],
            $config['assets'],
            $config['web_root'],
            $container->getParameter('kernel.debug') ? $config['output_folder_dev'] : $config['output_folder'],
            $config['source_root'],
            $cache_dir,
            $plugins,
            new Reference('hostnet_asset.node.executable'),
            new Reference('logger'),
            null,
            new Reference('hostnet_asset.split_strategy'),
        ]))->setPublic(false);

        $container->setDefinition('hostnet_asset.config', $bundler_config);

        // Register the main services.
        $import_finder = (new Definition(ImportFinder::class, [$container->getParameter('kernel.project_dir')]))
            ->setPublic(false);

        $cache = (new Definition(Cache::class, [$cache_dir . '/dependencies']))
            ->setPublic(false)
            ->addMethodCall('load');

        $plugin_api = (new Definition(PluginApi::class, [
            new Reference('hostnet_asset.import_finder'),
            new Reference('hostnet_asset.config'),
            new Reference('hostnet_asset.cache'),
            new Reference('hostnet_asset.build_config'),
        ]))
            ->setPublic(false);

        $plugin_activator = (new Definition(PluginActivator::class, [new Reference('hostnet_asset.plugin.api')]))
            ->setPublic(false);

        $build_plan = (new Definition(BuildConfig::class, [new Reference('hostnet_asset.config')]))
            ->setPublic(true);

        $bundler = (new Definition(Bundler::class, [
            new Reference('hostnet_asset.import_finder'),
            new Reference('hostnet_asset.config'),
        ]))
            ->setConfigurator([new Reference('hostnet_asset.plugin.activator'), 'ensurePluginsAreActivated'])
            ->setPublic(true);

        $container->setDefinition('hostnet_asset.import_finder', $import_finder);
        $container->setDefinition('hostnet_asset.cache', $cache);
        $container->setDefinition('hostnet_asset.plugin.api', $plugin_api);
        $container->setDefinition('hostnet_asset.plugin.activator', $plugin_activator);
        $container->setDefinition('hostnet_asset.build_config', $build_plan);
        $container->setDefinition('hostnet_asset.bundler', $bundler);

        // Register event listeners
        $this->configureEventListeners($container);
        // Register commands
        $this->configureCommands($container);
        // Register twig extensions
        $this->configureTwig($container);
    }

    private function configurePlugin($class, ContainerBuilder $container): Reference
    {
        $definition = (new Definition($class))
            ->setPublic(false);
        $container->setDefinition($class, $definition);

        return new Reference($class);
    }

    private function configureCommands(ContainerBuilder $container): void
    {
        $compile = (new Definition(CompileCommand::class, [
            new Reference('hostnet_asset.config'),
            new Reference('hostnet_asset.bundler'),
            new Reference('hostnet_asset.build_config'),
        ]))
            ->addTag('console.command')
            ->setPublic(false);

        $container->setDefinition('hostnet_asset.command.compile', $compile);

        if (!$container->getParameter('kernel.debug')) {
            return;
        }

        $debug = (new Definition(DebugCommand::class, [
            new Reference('hostnet_asset.config'),
            new Reference('hostnet_asset.import_finder'),
        ]))
            ->addTag('console.command')
            ->setPublic(false);

        $container->setDefinition('hostnet_asset.command.debug', $debug);
    }

    private function configureEventListeners(ContainerBuilder $container): void
    {
        if (false === $container->getParameter('kernel.debug')) {
            return;
        }
        $change_listener = (new Definition(AssetsChangeListener::class, [
            new Reference('hostnet_asset.bundler'),
            new Reference('hostnet_asset.build_config'),
        ]))
            ->addTag('kernel.event_listener', ['event' => KernelEvents::REQUEST, 'method' => 'onKernelRequest'])
            ->setPublic(false);

        $container->setDefinition('hostnet_asset.listener.assets_change', $change_listener);
    }

    private function configureTwig(ContainerBuilder $container): void
    {
        $ext = (new Definition(AssetExtension::class, [
            new Reference('hostnet_asset.config'),
        ]))
            ->addTag('twig.extension')
            ->setPublic(false);

        $container->setDefinition('hostnet_asset.twig.extension', $ext);
    }
}
