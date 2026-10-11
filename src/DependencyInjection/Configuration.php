<?php
declare(strict_types=1);
namespace Davidannebicque\TwigToWordBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder('twig_to_word');
        $tree->getRootNode()->children()
            ->scalarNode('default_theme')->defaultValue('default')->end()
            ->arrayNode('themes')
                ->useAttributeAsKey('name')
                ->arrayPrototype()
                    ->ignoreExtraKeys(false)
                    ->children()
                        ->scalarNode('extends')->end()
                        ->variableNode('styles')->defaultValue([])->end()
                        ->variableNode('document')->defaultValue([])->end()
                        ->variableNode('header')->defaultValue([])->end()
                        ->variableNode('footer')->defaultValue([])->end()
                    ->end()
                ->end()
            ->end()
        ->end();
        return $tree;
    }
}
