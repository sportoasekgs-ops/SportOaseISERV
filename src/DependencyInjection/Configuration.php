<?php

namespace SportOase\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sportoase');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->integerNode('max_students_per_period')
                    ->defaultValue(5)
                    ->info('Maximum number of students per booking period')
                ->end()
                ->integerNode('booking_advance_minutes')
                    ->defaultValue(60)
                    ->info('Minimum advance time for bookings in minutes')
                ->end()
                ->booleanNode('enable_notifications')
                    ->defaultTrue()
                    ->info('Enable email notifications for bookings')
                ->end()
                ->scalarNode('admin_email')
                    ->defaultValue('sportoase.kg@gmail.com')
                    ->info('Admin email for notifications')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
