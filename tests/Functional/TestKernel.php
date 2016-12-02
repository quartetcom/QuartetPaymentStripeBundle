<?php


namespace Quartet\Payment\StripeBundle\Functional;


use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use JMS\Payment\CoreBundle\JMSPaymentCoreBundle;
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
            new DoctrineBundle(),
            new JMSPaymentCoreBundle(),
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
