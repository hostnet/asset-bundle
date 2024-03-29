<?php
/**
 * @copyright 2017-present Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Bundle\AssetBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Process\ExecutableFinder;

final class Configuration implements ConfigurationInterface
{
    public const CONFIG_ROOT = 'hostnet_asset';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $finder       = new ExecutableFinder();
        $tree_builder = new TreeBuilder(self::CONFIG_ROOT);
        $root_node    = $tree_builder->getRootNode();
        $root_node
            ->children()
                ->arrayNode('files')
                    ->info('List of files which need to be compiled to modules.')
                    ->prototype('scalar')->end()
                    ->end()
                ->arrayNode('assets')
                    ->info('List of assets which need to be transpiled individually.')
                    ->prototype('scalar')->end()
                    ->end()
                ->arrayNode('include_paths')
                    ->info(
                        'List of additional folders from which modules can be loaded. ' .
                        'Relative paths are from the project root dir.'
                    )
                    ->prototype('scalar')->end()
                    ->end()
                ->arrayNode('excluded_files')
                    ->info('List of file to exclude from bundling')
                    ->prototype('scalar')->end()
                    ->end()
                ->scalarNode('source_root')
                    ->info('Location of the sources directory.')
                    ->defaultValue('app/Resources/assets')
                    ->end()
                ->scalarNode('web_root')
                    ->info('Location of the web directory.')
                    ->defaultValue('web')
                    ->end()
                ->scalarNode('output_folder')
                    ->info('Folder to dump production assets, relative to the project root.')
                    ->defaultValue('dist')
                    ->end()
                ->scalarNode('output_folder_dev')
                    ->info('Folder to dump development assets, relative to the project root.')
                    ->defaultValue('dev')
                    ->end()
                ->arrayNode('bin')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('node')
                            ->info('Path to the NodeJS binary.')
                            ->defaultValue(function () use ($finder) {
                                return $finder->find('node', '/usr/bin/node');
                            })
                            ->end()
                        ->scalarNode('node_modules')
                            ->info('Path to the application\'s node_modules.')
                            ->defaultValue('%kernel.project_dir%/node_modules')
                            ->end()
                        ->end()
                    ->end()
                ->arrayNode('plugins')
                    ->info('Configured plugins.')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->booleanNode('enabled')
                                ->defaultValue(true)
                            ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $tree_builder;
    }
}
