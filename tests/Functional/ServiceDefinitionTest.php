<?php


namespace Quartet\Payment\StripeBundle\Functional;

use Quartet\Stripe\Stripe;

class ServiceDefinitionTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        static::bootKernel();
    }

    public function testStripe()
    {
        $stripe = static::$kernel->getContainer()->get('quartet.payment.stripe');
        $this->assertInstanceOf(Stripe::class, $stripe);
    }
}
