<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Bundle\AssetBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Process\ExecutableFinder;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $tree_builder = new TreeBuilder();
        $finder       = new ExecutableFinder();
        $root_node    = $tree_builder->root('hostnet_asset');

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
                ->scalarNode('source_root')
                    ->info('Location of the sources directory.')
                    ->defaultValue('app/Resources/assets')
                    ->end()
                ->scalarNode('web_root')
                    ->info('Location of the web directory.')
                    ->defaultValue('web')
                    ->end()
                ->scalarNode('output_folder')
                    ->info('Folder to dump production assets, relative from the web_root.')
                    ->defaultValue('dist')
                    ->end()
                ->scalarNode('output_folder_dev')
                    ->info('Folder to dump development assets, relative from the web_root.')
                    ->defaultValue('dev')
                    ->end()
                ->arrayNode('bin')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('node')
                            ->isRequired()
                            ->info('Path to the NodeJS binary.')
                            ->defaultValue(function () use ($finder) {
                                return $finder->find('node', '/usr/bin/node');
                            })
                            ->end()
                        ->scalarNode('node_modules')
                            ->isRequired()
                            ->info('Path to the application\'s node_modules.')
                            ->defaultValue('%kernel.root_dir%/../node_modules')
                            ->end()
                        ->end()
                    ->end()
                ->arrayNode('loaders')
                    ->info('Enabled loaders.')
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
