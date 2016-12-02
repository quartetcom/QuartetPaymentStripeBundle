<?php


namespace Quartet\Payment\StripeBundle\Functional;

use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;

class ServiceDefinitionTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        static::bootKernel();
    }

    /**
     * @expectedException \JMS\Payment\CoreBundle\PluginController\Exception\PluginNotFoundException
     */
    public function testPluginControllerThrowExceptionUnlessValidPaymentSystemName()
    {
        $plugin = static::$kernel->getContainer()->get('payment.plugin_controller');
        $instruction = new PaymentInstruction(1000, 'jpy', 'wrong payment system name', $this->createExtendedData([]));
        $plugin->validatePaymentInstruction($instruction);
    }

    public function testStripePluginIsRegistered()
    {
        $plugin = static::$kernel->getContainer()->get('payment.plugin_controller');
        $instruction = new PaymentInstruction(1000, 'jpy', 'quartet_payment_stripe', $this->createExtendedData([
            'customer' => 111,
        ]));

        $plugin->validatePaymentInstruction($instruction);
    }

    /**
     * @param array $values
     *
     * @return ExtendedData
     */
    private function createExtendedData(array $values)
    {
        $data = new ExtendedData();

        foreach ($values as $key => $value) {
            $data->set($key, $value);
        }

        return $data;
    }
}
