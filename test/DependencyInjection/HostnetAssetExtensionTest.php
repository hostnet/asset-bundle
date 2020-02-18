<?php
/**
 * @copyright 2017-present Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Bundle\AssetBundle\DependencyInjection;

use Hostnet\Bundle\AssetBundle\Command\CompileCommand;
use Hostnet\Bundle\AssetBundle\EventListener\AssetsChangeListener;
use Hostnet\Bundle\AssetBundle\Twig\AssetExtension;
use Hostnet\Component\Resolver\Builder\Bundler;
use Hostnet\Component\Resolver\Import\ImportFinder;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Hostnet\Component\Resolver\Plugin\LessPlugin;
use Hostnet\Component\Resolver\Plugin\PluginInterface;
use Hostnet\Component\Resolver\Plugin\TsPlugin;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
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

    protected function setUp(): void
    {
        $this->hostnet_asset_extension = new HostnetAssetExtension();
    }

    public function testBlankConfig(): void
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.cache_dir', __DIR__);

        $this->hostnet_asset_extension->load([[]], $container);

        self::assertEquals([
            'service_container',
            'hostnet_asset.node.executable',
            'hostnet_asset.split_strategy',
            'hostnet_asset.config',
            'hostnet_asset.import_finder',
            'hostnet_asset.cache',
            'hostnet_asset.plugin.api',
            'hostnet_asset.plugin.activator',
            'hostnet_asset.build_config',
            'hostnet_asset.bundler',
            'hostnet_asset.listener.assets_change',
            'hostnet_asset.command.compile',
            'hostnet_asset.command.debug',
            'hostnet_asset.twig.extension',
        ], array_keys($container->getDefinitions()));
    }

    public function testLoadDebug(): void
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.cache_dir', __DIR__);

        $this->hostnet_asset_extension->load([['bin' => ['node' => '/usr/bin/node']]], $container);

        self::assertEquals([
            'service_container',
            'hostnet_asset.node.executable',
            'hostnet_asset.split_strategy',
            'hostnet_asset.config',
            'hostnet_asset.import_finder',
            'hostnet_asset.cache',
            'hostnet_asset.plugin.api',
            'hostnet_asset.plugin.activator',
            'hostnet_asset.build_config',
            'hostnet_asset.bundler',
            'hostnet_asset.listener.assets_change',
            'hostnet_asset.command.compile',
            'hostnet_asset.command.debug',
            'hostnet_asset.twig.extension',
        ], array_keys($container->getDefinitions()));

        $this->assertConfig($container, true, 'dev');

        $this->validateBaseServiceDefinitions($container);
    }

    public function testLoadProd(): void
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.cache_dir', __DIR__);

        $this->hostnet_asset_extension->load([['bin' => ['node' => '/usr/bin/node']]], $container);

        self::assertEquals([
            'service_container',
            'hostnet_asset.node.executable',
            'hostnet_asset.split_strategy',
            'hostnet_asset.config',
            'hostnet_asset.import_finder',
            'hostnet_asset.cache',
            'hostnet_asset.plugin.api',
            'hostnet_asset.plugin.activator',
            'hostnet_asset.build_config',
            'hostnet_asset.bundler',
            'hostnet_asset.command.compile',
            'hostnet_asset.twig.extension',
        ], array_keys($container->getDefinitions()));

        $this->assertConfig($container, false, 'dist');

        $this->validateBaseServiceDefinitions($container);
    }

    public function testLoadTypescript(): void
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.cache_dir', __DIR__);

        $this->hostnet_asset_extension->load(
            [
                [
                    'bin'     => ['node' => '/usr/bin/node'],
                    'plugins' => [TsPlugin::class => true],
                ],
            ],
            $container
        );

        self::assertEquals([
            'service_container',
            'hostnet_asset.node.executable',
            TsPlugin::class,
            'hostnet_asset.split_strategy',
            'hostnet_asset.config',
            'hostnet_asset.import_finder',
            'hostnet_asset.cache',
            'hostnet_asset.plugin.api',
            'hostnet_asset.plugin.activator',
            'hostnet_asset.build_config',
            'hostnet_asset.bundler',
            'hostnet_asset.listener.assets_change',
            'hostnet_asset.command.compile',
            'hostnet_asset.command.debug',
            'hostnet_asset.twig.extension',
        ], array_keys($container->getDefinitions()));

        $this->assertConfig($container, true, 'dev', [TsPlugin::class]);

        $this->validateBaseServiceDefinitions($container);
    }

    public function testLoadLess(): void
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.cache_dir', __DIR__);

        $this->hostnet_asset_extension->load(
            [
                [
                    'bin'     => ['node' => '/usr/bin/node'],
                    'plugins' => [LessPlugin::class => true],
                ],
            ],
            $container
        );

        self::assertEquals([
            'service_container',
            'hostnet_asset.node.executable',
            LessPlugin::class,
            'hostnet_asset.split_strategy',
            'hostnet_asset.config',
            'hostnet_asset.import_finder',
            'hostnet_asset.cache',
            'hostnet_asset.plugin.api',
            'hostnet_asset.plugin.activator',
            'hostnet_asset.build_config',
            'hostnet_asset.bundler',
            'hostnet_asset.listener.assets_change',
            'hostnet_asset.command.compile',
            'hostnet_asset.command.debug',
            'hostnet_asset.twig.extension',
        ], array_keys($container->getDefinitions()));

        $this->assertConfig($container, true, 'dev', [LessPlugin::class]);

        $this->validateBaseServiceDefinitions($container);
    }

    private function assertConfig(
        ContainerBuilder $container,
        bool $is_dev,
        string $output_folder,
        array $plugins = []
    ): void {
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

    private function validateBaseServiceDefinitions(ContainerBuilder $container): void
    {
        self::assertEquals((new Definition(Executable::class, [
            '/usr/bin/node',
            '%kernel.root_dir%/../node_modules',
        ]))->setPublic(false), $container->getDefinition('hostnet_asset.node.executable'));

        self::assertEquals(
            (new Definition(ImportFinder::class, [__DIR__]))->setPublic(false),
            $container->getDefinition('hostnet_asset.import_finder')
        );

        self::assertEquals(
            (new Definition(Bundler::class, [
                new Reference('hostnet_asset.import_finder'),
                new Reference('hostnet_asset.config'),
            ]))
                ->setPublic(true)
                ->setConfigurator([new Reference('hostnet_asset.plugin.activator'), 'ensurePluginsAreActivated']),
            $container->getDefinition('hostnet_asset.bundler')
        );

        self::assertEquals(
            (new Definition(CompileCommand::class, [
                new Reference('hostnet_asset.config'),
                new Reference('hostnet_asset.bundler'),
                new Reference('hostnet_asset.build_config'),
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

        if (!$container->getParameter('kernel.debug')) {
            return;
        }

        $this->validateDebugServiceDefinitions($container);
    }

    private function validateDebugServiceDefinitions(ContainerBuilder $container): void
    {
        self::assertEquals(
            (new Definition(AssetsChangeListener::class, [
                new Reference('hostnet_asset.bundler'),
                new Reference('hostnet_asset.build_config'),
            ]))
                ->setPublic(false)
                ->addTag('kernel.event_listener', [
                    'event'  => KernelEvents::REQUEST,
                    'method' => 'onKernelRequest',
                ]),
            $container->getDefinition('hostnet_asset.listener.assets_change')
        );
    }

    public function testBuild(): void
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.cache_dir', __DIR__);
        $container->setParameter('kernel.root_dir', __DIR__);

        $container->setDefinition('event_dispatcher', new Definition(EventDispatcher::class));
        $container->setDefinition('logger', new Definition(NullLogger::class));

        $this->hostnet_asset_extension->load(
            [
                [
                    'bin'     => ['node' => '/usr/bin/node'],
                    'plugins' => [
                        TsPlugin::class   => true,
                        LessPlugin::class => true,
                    ],
                ],
            ],
            $container
        );

        $container->compile();

        self::assertInstanceOf(Bundler::class, $container->get('hostnet_asset.bundler'));
    }

    public function testBuildNonPlugin(): void
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.cache_dir', __DIR__);
        $container->setParameter('kernel.root_dir', __DIR__);

        $container->setDefinition('event_dispatcher', new Definition(EventDispatcher::class));
        $container->setDefinition('logger', new Definition(NullLogger::class));

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('stdClass should implement ' . PluginInterface::class);

        $this->hostnet_asset_extension->load(
            [
                [
                    'bin'     => ['node' => '/usr/bin/node'],
                    'plugins' => [\stdClass::class => true],
                ],
            ],
            $container
        );
    }

    public function testBuildNonBuiltin(): void
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.cache_dir', __DIR__);
        $container->setParameter('kernel.root_dir', __DIR__);

        $container->setDefinition('event_dispatcher', new Definition(EventDispatcher::class));
        $container->setDefinition('logger', new Definition(NullLogger::class));

        $this->hostnet_asset_extension->load(
            [
                [
                    'bin'     => ['node' => '/usr/bin/node'],
                    'plugins' => [MockPlugin::class => true],
                ],
            ],
            $container
        );

        // make the config public
        $container->getDefinition('hostnet_asset.config')->setPublic(true);

        $container->compile();

        $plugins = $container->get('hostnet_asset.config')->getPlugins();

        self::assertCount(1, $plugins);
        self::assertInstanceOf(MockPlugin::class, $plugins[0]);
    }
}
