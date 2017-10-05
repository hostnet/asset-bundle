<?php
declare(strict_types = 1);
/**
 * @copyright 2017 Hostnet B.V.
 */
namespace Hostnet\Bundle\AssetBundle\DependencyInjection;

use Hostnet\Bundle\AssetBundle\Command\CompileCommand;
use Hostnet\Bundle\AssetBundle\EventListener\AssetsChangeListener;
use Hostnet\Bundle\AssetBundle\Twig\AssetExtension;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\Pipeline\ContentPipeline;
use Hostnet\Component\Resolver\Bundler\PipelineBundler;
use Hostnet\Component\Resolver\Bundler\Processor\IdentityProcessor;
use Hostnet\Component\Resolver\Bundler\Processor\JsonProcessor;
use Hostnet\Component\Resolver\Bundler\Processor\LessContentProcessor;
use Hostnet\Component\Resolver\Bundler\Processor\ModuleProcessor;
use Hostnet\Component\Resolver\Bundler\Processor\TsContentProcessor;
use Hostnet\Component\Resolver\Event\AssetEvents;
use Hostnet\Component\Resolver\EventListener\AngularHtmlListener;
use Hostnet\Component\Resolver\EventListener\CleanCssListener;
use Hostnet\Component\Resolver\EventListener\UglifyJsListener;
use Hostnet\Component\Resolver\Import\BuiltIn\AngularImportCollector;
use Hostnet\Component\Resolver\Import\BuiltIn\JsImportCollector;
use Hostnet\Component\Resolver\Import\BuiltIn\LessImportCollector;
use Hostnet\Component\Resolver\Import\BuiltIn\TsImportCollector;
use Hostnet\Component\Resolver\Import\ImportFinder;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Hostnet\Component\Resolver\Import\Nodejs\FileResolver;
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

        // Create config
        $bundler_config = (new Definition(ArrayConfig::class, [
            $container->getParameter('kernel.debug'),
            $container->getParameter('kernel.project_dir'),
            $config['files'],
            $config['assets'],
            $container->getParameter('kernel.debug') ? $config['output_folder_dev'] : $config['output_folder'],
            $config['web_root'],
            $config['source_root'],
            $container->getParameter('kernel.cache_dir') . '/assets'
        ]))
            ->setPublic(false);

        $container->setDefinition('hostnet_asset.config', $bundler_config);

        // Create the node information
        $node_executable = new Definition(Executable::class, [
            $config['bin']['node'],
            $config['bin']['node_modules'],
        ]);
        $node_executable->setPublic(false);

        $container->setDefinition('hostnet_asset.node.executable', $node_executable);

        // Register the main services.
        $import_collector = (new Definition(ImportFinder::class, [$container->getParameter('kernel.project_dir')]))
            ->setPublic(false);
        $pipeline         = (new Definition(ContentPipeline::class, [
            new Reference('event_dispatcher'),
            new Reference('logger'),
            new Reference('hostnet_asset.config'),
        ]))
            ->setPublic(false);

        $bundler = (new Definition(PipelineBundler::class, [
            new Reference('hostnet_asset.import_collector'),
            new Reference('hostnet_asset.pipline'),
            new Reference('logger'),
            new Reference('hostnet_asset.config'),
        ]))
            ->setPublic(true);

        $container->setDefinition('hostnet_asset.import_collector', $import_collector);
        $container->setDefinition('hostnet_asset.pipline', $pipeline);
        $container->setDefinition('hostnet_asset.bundler', $bundler);

        // Register event listeners
        $this->configureEventListeners($container);
        // Register commands
        $this->configureCommands($container);
        // Register twig extensions
        $this->configureTwig($container);

        // Configure the loaders
        $this->configureDefaultLoaders($container);

        foreach ($config['loaders'] as $name => $data) {
            switch ($name) {
                case 'ts':
                    $this->configureTsLoader($container);
                    break;
                case 'less':
                    $this->configureLessLoader($container);
                    break;
                case 'angular':
                    $this->configureAngularLoader($container);
                    break;
                default:
                    throw new InvalidConfigurationException(sprintf('Unknown loader %s.', $name));
            }
        }
    }

    private function configureDefaultLoaders(ContainerBuilder $container)
    {
        $js_file_resolver = (new Definition(FileResolver::class, [
            $container->getParameter('kernel.project_dir'),
            ['.js', '.json', '.node']
        ]))
            ->setPublic(false);

        $js_import_collector = (new Definition(JsImportCollector::class, [
            new Reference('hostnet_asset.import_collector.js.file_resolver')
        ]))
            ->setPublic(false)
            ->addTag('asset.import_collector');

        $js_processor     = (new Definition(IdentityProcessor::class, ['js', ContentState::PROCESSED]))
            ->setPublic(false)
            ->addTag('asset.processor');
        $css_processor    = (new Definition(IdentityProcessor::class, ['css']))
            ->setPublic(false)
            ->addTag('asset.processor');
        $html_processor   = (new Definition(IdentityProcessor::class, ['html']))
            ->setPublic(false)
            ->addTag('asset.processor');
        $module_processor = (new Definition(ModuleProcessor::class))
            ->setPublic(false)
            ->addTag('asset.processor');
        $json_processor   = (new Definition(JsonProcessor::class))
            ->setPublic(false)
            ->addTag('asset.processor');


        // Only enable the UglifyJs Transformer in non-dev
        if (!$container->getParameter('kernel.debug')) {
            $uglify_transformer = (new Definition(UglifyJsListener::class, [
                new Reference('hostnet_asset.node.executable'),
                $container->getParameter('kernel.cache_dir') . '/assets'
            ]))
                ->setPublic(false)
                ->addTag('kernel.event_listener', ['event' => AssetEvents::READY, 'method' => 'onPreWrite']);

            $cleancss_transformer = (new Definition(CleanCssListener::class, [
                new Reference('hostnet_asset.node.executable'),
                $container->getParameter('kernel.cache_dir') . '/assets'
            ]))
                ->setPublic(false)
                ->addTag('kernel.event_listener', ['event' => AssetEvents::READY, 'method' => 'onPreWrite']);

            $container->setDefinition('hostnet_asset.event_listener.uglify', $uglify_transformer);
            $container->setDefinition('hostnet_asset.event_listener.clean_css', $cleancss_transformer);
        }

        $container->setDefinition('hostnet_asset.import_collector.js.file_resolver', $js_file_resolver);
        $container->setDefinition('hostnet_asset.import_collector.js', $js_import_collector);
        $container->setDefinition('hostnet_asset.processor.module', $module_processor);
        $container->setDefinition('hostnet_asset.processor.js', $js_processor);
        $container->setDefinition('hostnet_asset.processor.json', $json_processor);
        $container->setDefinition('hostnet_asset.processor.css', $css_processor);
        $container->setDefinition('hostnet_asset.processor.html', $html_processor);
    }

    private function configureTsLoader(ContainerBuilder $container)
    {
        $file_resolver = (new Definition(FileResolver::class, [
            $container->getParameter('kernel.project_dir'),
            ['.ts', '.js', '.json', '.node']
        ]))
            ->setPublic(false);

        $js_fallback_collector = $js_import_collector = (new Definition(JsImportCollector::class, [
            new Reference('hostnet_asset.import_collector.ts.file_resolver')
        ]))
            ->setPublic(false);

        $collector  = (new Definition(TsImportCollector::class, [
            new Reference('hostnet_asset.import_collector.ts.js_fallback_collector'),
            new Reference('hostnet_asset.import_collector.ts.file_resolver')
        ]))
            ->setPublic(false)
            ->addTag('asset.import_collector');
        $transpiler = (new Definition(TsContentProcessor::class, [new Reference('hostnet_asset.node.executable')]))
            ->setPublic(false)
            ->addTag('asset.processor');

        $container->setDefinition('hostnet_asset.import_collector.ts.js_fallback_collector', $js_fallback_collector);
        $container->setDefinition('hostnet_asset.import_collector.ts.file_resolver', $file_resolver);
        $container->setDefinition('hostnet_asset.import_collector.ts', $collector);
        $container->setDefinition('hostnet_asset.processor.ts', $transpiler);
    }

    private function configureLessLoader(ContainerBuilder $container)
    {
        $collector  = (new Definition(LessImportCollector::class))
            ->setPublic(false)
            ->addTag('asset.import_collector');
        $transpiler = (new Definition(LessContentProcessor::class, [new Reference('hostnet_asset.node.executable')]))
            ->setPublic(false)
            ->addTag('asset.processor');

        $container->setDefinition('hostnet_asset.import_collector.less', $collector);
        $container->setDefinition('hostnet_asset.processor.less', $transpiler);
    }

    private function configureAngularLoader(ContainerBuilder $container)
    {
        $collector   = (new Definition(AngularImportCollector::class))
            ->setPublic(false)
            ->addTag('asset.import_collector');
        $transformer = (new Definition(AngularHtmlListener::class, [
            new Reference('hostnet_asset.config'),
            new Reference('hostnet_asset.pipline')
        ]))
            ->setPublic(false)
            ->addTag('kernel.event_listener', ['event' => AssetEvents::POST_PROCESS, 'method' => 'onPostTranspile']);

        $container->setDefinition('hostnet_asset.import_collector.angular', $collector);
        $container->setDefinition('hostnet_asset.event_listener.angular', $transformer);
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
