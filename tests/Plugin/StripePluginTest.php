<?php


namespace Quartet\Payment\StripeBundle\Plugin;


use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInterface;
use JMS\Payment\CoreBundle\Plugin\PluginInterface;
use Psr\Log\LoggerInterface;
use Quartet\Stripe\Api\Charge;
use Quartet\Stripe\Api\Model;
use Quartet\Stripe\Stripe;
use Stripe\Error;

class StripePluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $charge;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var StripePlugin
     */
    private $plugin;

    protected function setUp()
    {
        parent::setUp();

        $stripe = $this->createMock(Stripe::class);

        $stripe
            ->expects($this->once())
            ->method('charges')
            ->willReturn($this->charge = $this->createMock(Charge::class));

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->plugin = new StripePlugin($stripe, $this->logger);
    }

    /**
     *
     * @dataProvider provideErrorTests
     * @expectedException \JMS\Payment\CoreBundle\Plugin\Exception\FinancialException
     *
     * @param                               $exception
     * @param \PHPUnit_Framework_Constraint $logMessage
     */
    public function testApproveAndDepositThrowsExceptionIfStripeRespondError($exception, \PHPUnit_Framework_Constraint $logMessage = null)
    {
        $this->charge
            ->expects($this->once())
            ->method('create')
            ->with([
                'currency' => $currency = 'jpy',
                'amount' => $amount = 2000,
                'customer' => $customer = 111,
                'capture' => true,
            ])
            ->willThrowException($this->createMock($exception));

        $transaction = $this->createFinancialTransactionMock($amount, $currency, [
            StripePlugin::ATTR_CHARGE_CUSTOMER => $customer,
        ]);

        if ($logMessage) {
            $this->logger->expects($this->once())->method('critical')->with($logMessage);
        }

        $this->plugin->approveAndDeposit($transaction, false);
    }

    /**
     * @return array
     */
    public function provideErrorTests()
    {
        return [
            [Error\RateLimit::class, $this->stringContains('rate limit')],
            [Error\Authentication::class, $this->stringContains('authentication')],
            [Error\ApiConnection::class, $this->stringContains('connection')],
            [Error\Base::class],
        ];
    }

    /**
     * @dataProvider provideApproveAndDepositTests
     *
     * @param       $label
     * @param       $amount
     * @param       $currency
     * @param array $data
     * @param array $expectedRequestParams
     */
    public function testApproveAndDeposit($label, $amount, $currency, array $data, array $expectedRequestParams)
    {
        $this->charge
            ->expects($this->once())
            ->method('create')
            ->with($expectedRequestParams)
            ->willReturn($charge = $this->createChargeMock(8888, 9999));

        $transaction = $this->createFinancialTransactionMock($amount, $currency, $data);


        $transaction
            ->expects($this->once())
            ->method('setReferenceNumber')
            ->with(8888);

        $transaction
            ->expects($this->once())
            ->method('setProcessedAmount')
            ->with(9999);

        $transaction
            ->expects($this->once())
            ->method('setResponseCode')
            ->with(PluginInterface::RESPONSE_CODE_SUCCESS);

        $transaction
            ->expects($this->once())
            ->method('setReasonCode')
            ->with(PluginInterface::REASON_CODE_SUCCESS);

        $this->plugin->approveAndDeposit($transaction, false);
    }

    /**
     * @return array
     */
    public function provideApproveAndDepositTests()
    {
        $amount = 2000;
        $currency = 'jpy';

        return [[
            'test with card', $amount, $currency, [
                StripePlugin::ATTR_CHARGE_CARD => 'card'
            ], [
                'currency' => $currency,
                'amount' => $amount,
                'source' => 'card',
                'capture' => true,
            ]
        ], [
            'test with customer', $amount, $currency, [
                StripePlugin::ATTR_CHARGE_CUSTOMER => 'customer 1',
            ], [
                'currency' => $currency,
                'amount' => $amount,
                'customer' => 'customer 1',
                'capture' => true,
            ]
        ]];
    }

    /**
     * @param       $amount
     * @param       $currency
     * @param array $data
     *
     * @return FinancialTransactionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createFinancialTransactionMock($amount, $currency, array $data)
    {
        $payment = $this->createPaymentMock($this->createPaymentInstructionMock($currency));

        $transaction = $this->createMock(FinancialTransactionInterface::class);
        $transaction
            ->expects($this->once())
            ->method('getRequestedAmount')
            ->willReturn($amount);


        $transaction
            ->expects($this->any())
            ->method('getPayment')
            ->willReturn($payment);

        $transaction
            ->expects($this->any())
            ->method('getExtendedData')
            ->willReturn($extendedData = new ExtendedData());

        foreach ($data as $key => $value) {
            $extendedData->set($key, $value);
        }

        return $transaction;
    }

    /**
     * @param PaymentInstructionInterface $paymentInstruction
     *
     * @return PaymentInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createPaymentMock(PaymentInstructionInterface $paymentInstruction)
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment
            ->expects($this->any())
            ->method('getPaymentInstruction')
            ->willReturn($paymentInstruction);

        return $payment;
    }

    /**
     * @param $currency
     *
     * @return PaymentInstructionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createPaymentInstructionMock($currency)
    {
        $instruction = $this->createMock(PaymentInstructionInterface::class);
        $instruction
            ->expects($this->any())
            ->method('getCurrency')
            ->willReturn($currency);

        return $instruction;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Model\Charge
     */
    private function createChargeMock($id, $amount)
    {
        $wrapper = $this->createMock(Model\Charge::class);
        $charge = new \Stripe\Charge($id);
        $charge->amount = $amount;

        $wrapper->expects($this->any())->method('value')->willReturn($charge);

        return $wrapper;
    }
}
