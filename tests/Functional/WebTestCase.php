<?php


namespace Quartet\Payment\StripeBundle\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;

class WebTestCase extends BaseWebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected static function getKernelClass()
    {
        return TestKernel::class;
    }
}
