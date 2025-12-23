<?php

namespace InSquare\PimcoreSimpleSearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('in_square_pimcore_simple_search');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            // Database configuration
            ->scalarNode('table_name')
            ->defaultValue('search_index')
            ->info('Name of the MySQL table for search index')
            ->end()

            // Localization
            ->arrayNode('locales')
            ->info('Supported locales for indexing')
            ->scalarPrototype()->end()
            ->defaultValue(['en'])
            ->requiresAtLeastOneElement()
            ->end()

            // Multi-site support
            ->arrayNode('sites')
            ->info('Site IDs to index (0 for default)')
            ->integerPrototype()->end()
            ->defaultValue([0])
            ->requiresAtLeastOneElement()
            ->end()

            // What to index
            ->booleanNode('index_documents')
            ->info('Enable indexing of Pimcore documents (pages)')
            ->defaultTrue()
            ->end()

            ->booleanNode('index_objects')
            ->info('Enable indexing of Pimcore data objects')
            ->defaultTrue()
            ->end()

            // Content limits
            ->integerNode('max_content_length')
            ->info('Maximum length of indexed content (characters)')
            ->defaultValue(20000)
            ->min(100)
            ->end()

            ->integerNode('snippet_length')
            ->info('Length of search result snippets (characters)')
            ->defaultValue(220)
            ->min(50)
            ->end()

            // Search configuration
            ->booleanNode('use_boolean_mode')
            ->info('Use MySQL BOOLEAN MODE for full-text search')
            ->defaultTrue()
            ->end()

            ->integerNode('min_search_length')
            ->info('Minimum search query length')
            ->defaultValue(3)
            ->min(1)
            ->end()
            ->end();

        return $treeBuilder;
    }
}
