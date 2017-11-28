<?php
declare(strict_types = 1);
/**
 * @copyright 2017 Hostnet B.V.
 */
namespace Hostnet\Bundle\AssetBundle\DependencyInjection;

use Hostnet\Bundle\AssetBundle\Command\CompileCommand;
use Hostnet\Bundle\AssetBundle\EventListener\AssetsChangeListener;
use Hostnet\Bundle\AssetBundle\Twig\AssetExtension;
use Hostnet\Component\Resolver\Bundler\Pipeline\ContentPipeline;
use Hostnet\Component\Resolver\Bundler\PipelineBundler;
use Hostnet\Component\Resolver\Bundler\Runner\RunnerInterface;
use Hostnet\Component\Resolver\Cache\Cache;
use Hostnet\Component\Resolver\FileSystem\FileWriter;
use Hostnet\Component\Resolver\Import\ImportFinder;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Hostnet\Component\Resolver\Plugin\AngularPlugin;
use Hostnet\Component\Resolver\Plugin\CorePlugin;
use Hostnet\Component\Resolver\Plugin\LessPlugin;
use Hostnet\Component\Resolver\Plugin\MinifyPlugin;
use Hostnet\Component\Resolver\Plugin\PluginActivator;
use Hostnet\Component\Resolver\Plugin\PluginApi;
use Hostnet\Component\Resolver\Plugin\PluginInterface;
use Hostnet\Component\Resolver\Plugin\TsPlugin;
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
    public function load(array $configs, ContainerBuilder $container)
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
            if (! $is_enabled) {
                continue;
            }

            if (! is_subclass_of($name, PluginInterface::class)) {
                throw new InvalidConfigurationException(sprintf(
                    'Class %s should implement %s.',
                    $name,
                    PluginInterface::class
                ));
            }

            $plugins[] = $this->configurePlugin($name, $container);
        }

        $cache_dir = $container->getParameter('kernel.cache_dir') . '/assets';
        // Create config
        $bundler_config = (new Definition(ArrayConfig::class, [
            $container->getParameter('kernel.debug'),
            $container->getParameter('kernel.project_dir'),
            $config['include_paths'],
            $config['files'],
            $config['assets'],
            $config['web_root'],
            $container->getParameter('kernel.debug') ? $config['output_folder_dev'] : $config['output_folder'],
            $config['source_root'],
            $cache_dir,
            $config['enable_unix_socket'],
            $plugins,
            new Reference('hostnet_asset.node.executable'),
            new Reference('event_dispatcher'),
            new Reference('logger')
        ]))
            ->setPublic(false);

        $container->setDefinition('hostnet_asset.config', $bundler_config);

        $runner = (new Definition(RunnerInterface::class))
            ->setFactory([new Reference('hostnet_asset.config'), 'getRunner'])
            ->setPublic(false);

        $container->setDefinition('hostnet_asset.runner', $runner);

        // Register the main services.
        $import_finder = (new Definition(ImportFinder::class, [$container->getParameter('kernel.project_dir')]))
            ->setPublic(false);
        $writer        = (new Definition(FileWriter::class, [$container->getParameter('kernel.project_dir')]))
            ->setPublic(false);

        $pipeline = (new Definition(ContentPipeline::class, [
            new Reference('event_dispatcher'),
            new Reference('logger'),
            new Reference('hostnet_asset.config'),
            new Reference('hostnet_asset.file_writer'),
        ]))
            ->setPublic(false);

        $cache = (new Definition(Cache::class, [$cache_dir . '/dependencies']))
            ->setPublic(false)
            ->addMethodCall('load');

        $plugin_api = (new Definition(PluginApi::class, [
            new Reference('hostnet_asset.pipline'),
            new Reference('hostnet_asset.import_finder'),
            new Reference('hostnet_asset.config'),
            new Reference('hostnet_asset.cache')
        ]))
            ->setPublic(false);

        $plugin_activator = (new Definition(PluginActivator::class, [new Reference('hostnet_asset.plugin.api')]))
            ->setPublic(false);

        $bundler = (new Definition(PipelineBundler::class, [
            new Reference('hostnet_asset.import_finder'),
            new Reference('hostnet_asset.pipline'),
            new Reference('logger'),
            new Reference('hostnet_asset.config'),
            new Reference('hostnet_asset.runner'),
        ]))
            ->setConfigurator([new Reference('hostnet_asset.plugin.activator'), 'ensurePluginsAreActivated'])
            ->setPublic(true);

        $container->setDefinition('hostnet_asset.import_finder', $import_finder);
        $container->setDefinition('hostnet_asset.file_writer', $writer);
        $container->setDefinition('hostnet_asset.pipline', $pipeline);
        $container->setDefinition('hostnet_asset.cache', $cache);
        $container->setDefinition('hostnet_asset.plugin.api', $plugin_api);
        $container->setDefinition('hostnet_asset.plugin.activator', $plugin_activator);
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

    private function configureCommands(ContainerBuilder $container)
    {
        $compile = (new Definition(CompileCommand::class, [
            new Reference('hostnet_asset.bundler'),
            new Reference('hostnet_asset.config'),
        ]))
            ->addTag('console.command')
            ->setPublic(false);

        $container->setDefinition('hostnet_asset.command.compile', $compile);
    }

    private function configureEventListeners(ContainerBuilder $container)
    {
        if (! $container->getParameter('kernel.debug')) {
            return;
        }
        $change_listener = (new Definition(AssetsChangeListener::class, [
            new Reference('hostnet_asset.bundler'),
            new Reference('hostnet_asset.config'),
        ]))
            ->addTag('kernel.event_listener', ['event' => KernelEvents::RESPONSE, 'method' => 'onKernelResponse'])
            ->setPublic(false);

        $container->setDefinition('hostnet_asset.listener.assets_change', $change_listener);
    }

    private function configureTwig(ContainerBuilder $container)
    {
        $ext = (new Definition(AssetExtension::class, [
            new Reference('hostnet_asset.config'),
        ]))
            ->addTag('twig.extension')
            ->setPublic(false);

        $container->setDefinition('hostnet_asset.twig.extension', $ext);
    }
}
