<?php


namespace Quartet\Payment\StripeBundle\DependencyInjection;


use Quartet\Stripe\Stripe;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;

class QuartetPaymentStripeExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (!class_exists(Stripe::class)) {
            throw new LogicException(sprintf('Bundle cannot be loaded as the %s is not found. Please install quartet/stripe-bundle.', Stripe::class));
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('service.yml');

        $plugin = $container->getDefinition('quartet.payment.stripe_plugin')
            ->replaceArgument(0, new Reference($config['stripe_service']));

        if (isset($config['logger'])) {
            $plugin->replaceArgument(1, new Reference($config['logger']));
        }
    }
}
