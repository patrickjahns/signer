<?php

/**
 * @author Patrick Jahns <github@patrickjahns.de>
 * @copyright Copyright (c) 2019, Patrick Jahns.
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace Signer\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     * @suppress PhanPossiblyNonClassMethodCall
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(SignerExtension::getLocalAlias());

        $rootNode
            ->children()
                ->scalarNode('jwk')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('workspace')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('keyservice')
                    ->isRequired()
                    ->validate()
                        ->ifTrue(function ($value) {
                            return count($value) > 1;
                        })
                        ->thenInvalid('only one keyservice can be configured')
                    ->end()
                    ->validate()
                        ->ifEmpty()
                        ->thenInvalid('at least one keyservice must be configured')
                    ->end()
                    ->children()
                        ->arrayNode('file')
                            ->children()
                                ->scalarNode('lookup_directory')
                                    ->isRequired()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('vault_secret')
                            ->children()
                                ->scalarNode('url')
                                    ->isRequired()
                                ->end()
                                ->scalarNode('namespace')
                                    ->isRequired()
                                ->end()
                                ->arrayNode('auth')
                                        ->isRequired()
                                        ->validate()
                                            ->ifTrue(function ($value) {
                                                return count($value) > 1;
                                            })
                                            ->thenInvalid('only one authentcation method can be configured')
                                        ->end()
                                        ->validate()
                                            ->ifEmpty()
                                            ->thenInvalid('at least one authentication method needs to be set')
                                        ->end()
                                    ->children()
                                        ->scalarNode('token')
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->arrayNode('credentials')
                                            ->children()
                                                ->scalarNode('username')
                                                    ->isRequired()
                                                    ->cannotBeEmpty()
                                                ->end()
                                                ->scalarNode('password')
                                                    ->isRequired()
                                                    ->cannotBeEmpty()
                                                ->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('role')
                                            ->children()
                                                ->scalarNode('id')
                                                    ->isRequired()
                                                    ->cannotBeEmpty()
                                                ->end()
                                                ->scalarNode('secret')
                                                    ->isRequired()
                                                    ->cannotBeEmpty()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
