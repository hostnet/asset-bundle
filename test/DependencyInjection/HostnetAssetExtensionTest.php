<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Bundle\AssetBundle\DependencyInjection;

use Hostnet\Bundle\AssetBundle\Command\CompileCommand;
use Hostnet\Bundle\AssetBundle\EventListener\AssetsChangeListener;
use Hostnet\Bundle\AssetBundle\Twig\AssetExtension;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\Pipeline\ContentPipeline;
use Hostnet\Component\Resolver\Bundler\PipelineBundler;
use Hostnet\Component\Resolver\Bundler\Processor\IdentityProcessor;
use Hostnet\Component\Resolver\Bundler\Processor\JsonProcessor;
use Hostnet\Component\Resolver\Bundler\Processor\ModuleProcessor;
use Hostnet\Component\Resolver\Import\BuiltIn\JsImportCollector;
use Hostnet\Component\Resolver\Import\ImportFinder;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Hostnet\Component\Resolver\Import\Nodejs\FileResolver;
use Hostnet\Component\Resolver\Plugin\AngularPlugin;
use Hostnet\Component\Resolver\Plugin\LessPlugin;
use Hostnet\Component\Resolver\Plugin\TsPlugin;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @covers \Hostnet\Bundle\AssetBundle\DependencyInjection\Configuration
 * @covers \Hostnet\Bundle\AssetBundle\DependencyInjection\HostnetAssetExtension
 */
class HostnetAssetExtensionTest extends TestCase
{
    /**
     * @var HostnetAssetExtension
     */
    private $hostnet_asset_extension;

    protected function setUp()
    {
        $this->hostnet_asset_extension = new HostnetAssetExtension();
    }

    public function testBlankConfig()
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.cache_dir', __DIR__);

        $this->hostnet_asset_extension->load([[]], $container);

        self::assertEquals([
            'service_container',
            'hostnet_asset.node.executable',
            'hostnet_asset.config',
            'hostnet_asset.runner.uglify_js',
            'hostnet_asset.import_finder',
            'hostnet_asset.file_writer',
            'hostnet_asset.pipline',
            'hostnet_asset.cache',
            'hostnet_asset.plugin.api',
            'hostnet_asset.plugin.activator',
            'hostnet_asset.bundler',
            'hostnet_asset.listener.assets_change',
            'hostnet_asset.command.compile',
            'hostnet_asset.twig.extension',
        ], array_keys($container->getDefinitions()));
    }

    public function testLoadDebug()
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.cache_dir', __DIR__);

        $this->hostnet_asset_extension->load([[
            'bin' => ['node' => '/usr/bin/node']
        ]], $container);

        self::assertEquals([
            'service_container',
            'hostnet_asset.node.executable',
            'hostnet_asset.config',
            'hostnet_asset.runner.uglify_js',
            'hostnet_asset.import_finder',
            'hostnet_asset.file_writer',
            'hostnet_asset.pipline',
            'hostnet_asset.cache',
            'hostnet_asset.plugin.api',
            'hostnet_asset.plugin.activator',
            'hostnet_asset.bundler',
            'hostnet_asset.listener.assets_change',
            'hostnet_asset.command.compile',
            'hostnet_asset.twig.extension',
        ], array_keys($container->getDefinitions()));

        $this->assertConfig($container, true, 'dev');

        $this->validateBaseServiceDefinitions($container);
    }

    public function testLoadProd()
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.cache_dir', __DIR__);

        $this->hostnet_asset_extension->load([[
            'bin' => ['node' => '/usr/bin/node']
        ]], $container);

        self::assertEquals([
            'service_container',
            'hostnet_asset.node.executable',
            'hostnet_asset.config',
            'hostnet_asset.runner.uglify_js',
            'hostnet_asset.import_finder',
            'hostnet_asset.file_writer',
            'hostnet_asset.pipline',
            'hostnet_asset.cache',
            'hostnet_asset.plugin.api',
            'hostnet_asset.plugin.activator',
            'hostnet_asset.bundler',
            'hostnet_asset.command.compile',
            'hostnet_asset.twig.extension',
        ], array_keys($container->getDefinitions()));

        $this->assertConfig($container, false, 'dist');

        $this->validateBaseServiceDefinitions($container);
    }

    public function testLoadTypescript()
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.cache_dir', __DIR__);

        $this->hostnet_asset_extension->load([[
            'bin' => ['node' => '/usr/bin/node'],
            'plugins' => [TsPlugin::class => true]
        ]], $container);

        self::assertEquals([
            'service_container',
            'hostnet_asset.node.executable',
            TsPlugin::class,
            'hostnet_asset.config',
            'hostnet_asset.runner.uglify_js',
            'hostnet_asset.import_finder',
            'hostnet_asset.file_writer',
            'hostnet_asset.pipline',
            'hostnet_asset.cache',
            'hostnet_asset.plugin.api',
            'hostnet_asset.plugin.activator',
            'hostnet_asset.bundler',
            'hostnet_asset.listener.assets_change',
            'hostnet_asset.command.compile',
            'hostnet_asset.twig.extension',
        ], array_keys($container->getDefinitions()));

        $this->assertConfig($container, true, 'dev', [TsPlugin::class]);

        $this->validateBaseServiceDefinitions($container);
    }

    public function testLoadLess()
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.cache_dir', __DIR__);

        $this->hostnet_asset_extension->load([[
            'bin' => ['node' => '/usr/bin/node'],
            'plugins' => [LessPlugin::class => true]
        ]], $container);

        self::assertEquals([
            'service_container',
            'hostnet_asset.node.executable',
            LessPlugin::class,
            'hostnet_asset.config',
            'hostnet_asset.runner.uglify_js',
            'hostnet_asset.import_finder',
            'hostnet_asset.file_writer',
            'hostnet_asset.pipline',
            'hostnet_asset.cache',
            'hostnet_asset.plugin.api',
            'hostnet_asset.plugin.activator',
            'hostnet_asset.bundler',
            'hostnet_asset.listener.assets_change',
            'hostnet_asset.command.compile',
            'hostnet_asset.twig.extension',
        ], array_keys($container->getDefinitions()));

        $this->assertConfig($container, true, 'dev', [LessPlugin::class]);

        $this->validateBaseServiceDefinitions($container);
    }

    public function testLoadAngular()
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.cache_dir', __DIR__);

        $this->hostnet_asset_extension->load([[
            'bin' => ['node' => '/usr/bin/node'],
            'plugins' => [AngularPlugin::class => true]
        ]], $container);

        self::assertEquals([
            'service_container',
            'hostnet_asset.node.executable',
            AngularPlugin::class,
            'hostnet_asset.config',
            'hostnet_asset.runner.uglify_js',
            'hostnet_asset.import_finder',
            'hostnet_asset.file_writer',
            'hostnet_asset.pipline',
            'hostnet_asset.cache',
            'hostnet_asset.plugin.api',
            'hostnet_asset.plugin.activator',
            'hostnet_asset.bundler',
            'hostnet_asset.listener.assets_change',
            'hostnet_asset.command.compile',
            'hostnet_asset.twig.extension',
        ], array_keys($container->getDefinitions()));

        $this->assertConfig($container, true, 'dev', [AngularPlugin::class]);

        $this->validateBaseServiceDefinitions($container);
    }

    private function assertConfig(ContainerBuilder $container, bool $is_dev, string $output_folder, array $plugins = [])
    {
        $definition = $container->getDefinition('hostnet_asset.config');

        self::assertSame($is_dev, $definition->getArgument(0));
        self::assertSame(__DIR__, $definition->getArgument(1));
        self::assertSame('web', $definition->getArgument(5));
        self::assertSame($output_folder, $definition->getArgument(6));

        $plugin_references = [];
        foreach ($plugins as $plugin) {
            $plugin_references[] = new Reference($plugin);
        }
        self::assertEquals($plugin_references, $definition->getArgument(9));
    }

    private function validateBaseServiceDefinitions(ContainerBuilder $container)
    {
        self::assertEquals((new Definition(Executable::class, [
            '/usr/bin/node',
            '%kernel.root_dir%/../node_modules'
        ]))->setPublic(false), $container->getDefinition('hostnet_asset.node.executable'));

        self::assertEquals(
            (new Definition(ImportFinder::class, [__DIR__]))->setPublic(false),
            $container->getDefinition('hostnet_asset.import_finder')
        );

        self::assertEquals((new Definition(ContentPipeline::class, [
            new Reference('event_dispatcher'),
            new Reference('logger'),
            new Reference('hostnet_asset.config'),
            new Reference('hostnet_asset.file_writer')
        ]))->setPublic(false), $container->getDefinition('hostnet_asset.pipline'));

        self::assertEquals(
            (new Definition(PipelineBundler::class, [
                new Reference('hostnet_asset.import_finder'),
                new Reference('hostnet_asset.pipline'),
                new Reference('logger'),
                new Reference('hostnet_asset.config'),
                new Reference('hostnet_asset.runner.uglify_js'),
            ]))
                ->setPublic(true)
                ->setConfigurator([new Reference('hostnet_asset.plugin.activator'), 'ensurePluginsAreActivated']),
            $container->getDefinition('hostnet_asset.bundler')
        );

        self::assertEquals(
            (new Definition(CompileCommand::class, [
                new Reference('hostnet_asset.bundler'),
                new Reference('hostnet_asset.config'),
            ]))
                ->setPublic(false)
                ->addTag('console.command'),
            $container->getDefinition('hostnet_asset.command.compile')
        );

        self::assertEquals(
            (new Definition(AssetExtension::class, [
                new Reference('hostnet_asset.config'),
            ]))
                ->setPublic(false)
                ->addTag('twig.extension'),
            $container->getDefinition('hostnet_asset.twig.extension')
        );

        if ($container->getParameter('kernel.debug')) {
            $this->validateDebugServiceDefinitions($container);
        }
    }

    private function validateDebugServiceDefinitions(ContainerBuilder $container)
    {
        self::assertEquals(
            (new Definition(AssetsChangeListener::class, [
                new Reference('hostnet_asset.bundler'),
                new Reference('hostnet_asset.config'),
            ]))
                ->setPublic(false)
                ->addTag('kernel.event_listener', [
                    'event' => KernelEvents::RESPONSE,
                    'method' => 'onKernelResponse'
                ]),
            $container->getDefinition('hostnet_asset.listener.assets_change')
        );
    }

    public function testBuild()
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.cache_dir', __DIR__);
        $container->setParameter('kernel.root_dir', __DIR__);

        $container->setDefinition('event_dispatcher', new Definition(EventDispatcher::class));
        $container->setDefinition('logger', new Definition(NullLogger::class));

        $this->hostnet_asset_extension->load([[
            'bin' => ['node' => '/usr/bin/node'],
            'plugins' => [
                TsPlugin::class => true,
                LessPlugin::class => true,
                AngularPlugin::class => true
            ]
        ]], $container);

        $container->compile();

        self::assertInstanceOf(PipelineBundler::class, $container->get('hostnet_asset.bundler'));
    }
}
