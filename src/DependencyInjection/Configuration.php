<?php


namespace Quartet\Payment\StripeBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $tree = $builder->root('quartet_payment_stripe');
        $tree
            ->children()
                ->scalarNode('stripe_service')->isRequired()->end()
            ->end()
        ;

        return $builder;
    }
}
