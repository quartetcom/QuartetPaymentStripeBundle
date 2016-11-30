<?php


namespace Quartet\Payment\StripeBundle\Functional;


use Quartet\Payment\StripeBundle\QuartetPaymentStripeBundle;
use Quartet\StripeBundle\QuartetStripeBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new QuartetStripeBundle(),
            new QuartetPaymentStripeBundle(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config.yml');
    }
}
