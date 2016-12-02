<?php


namespace Quartet\Payment\StripeBundle\Plugin;


use JMS\Payment\CoreBundle\Model\ExtendedDataInterface;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;
use JMS\Payment\CoreBundle\Plugin\ErrorBuilder;
use JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;
use JMS\Payment\CoreBundle\Plugin\PluginInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Quartet\Stripe\Api\Model\Charge;
use Quartet\Stripe\Stripe;
use Stripe\Error;

class StripePlugin extends AbstractPlugin
{
    const ATTR_CHARGE_CARD      = 'source';
    const ATTR_CHARGE_CUSTOMER  = 'customer';

    /**
     * @var Stripe
     */
    private $stripe;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * StripePlugin constructor.
     *
     * @param Stripe          $stripe
     * @param LoggerInterface $logger
     */
    public function __construct(Stripe $stripe, LoggerInterface $logger = null)
    {
        parent::__construct();

        $this->stripe = $stripe;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function checkPaymentInstruction(PaymentInstructionInterface $instruction)
    {
        $errorBuilder = new ErrorBuilder();

        $data = $instruction->getExtendedData();

        if (!$data->has(self::ATTR_CHARGE_CARD) && !$data->has(self::ATTR_CHARGE_CUSTOMER)) {
            $errorBuilder->addDataError('card', 'form.error.required');
        }

        if ($errorBuilder->hasErrors()) {
            throw $errorBuilder->getException();
        }
    }


    /**
     * {@inheritdoc}
     */
    public function approveAndDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        $payment = $transaction->getPayment();

        try {
            $charge = $this->createCharge(
                $transaction->getRequestedAmount(),
                $payment->getPaymentInstruction()->getCurrency(),
                $transaction->getExtendedData(),
                true
            );

            $value = $charge->value();

            $transaction->setReferenceNumber($value->id);
            $transaction->setProcessedAmount($value->amount);
            $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
            $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);
        } catch (Error\RateLimit $e) {
            $this->logger->critical('Stripe: api rate limit exceed.', [
                'message' => $e->getMessage(),
                'response' => $e->getHttpBody(),
            ]);

            throw $this->createFinancialException($e, $transaction);
        } catch (Error\Authentication $e) {
            $this->logger->critical('Stripe: authentication failed', [
                'message'  => $e->getMessage(),
                'response' => $e->getHttpBody(),
            ]);

            throw $this->createFinancialException($e, $transaction);
        } catch (Error\ApiConnection $e) {
            $this->logger->critical('Stripe: connection failed');

            throw $this->createFinancialException($e, $transaction);
        } catch (Error\Base $e) {
            throw $this->createFinancialException($e, $transaction);
        }
    }

    /**
     * @param                       $amount
     * @param                       $currency
     * @param ExtendedDataInterface $data
     * @param bool                  $captureImmediately
     *
     * @return Charge
     */
    private function createCharge($amount, $currency, ExtendedDataInterface $data, $captureImmediately = false)
    {
        if ($data->has(self::ATTR_CHARGE_CUSTOMER)) {
            return $this->createChargeWithCustomer($amount, $currency, $data->get(self::ATTR_CHARGE_CUSTOMER), $captureImmediately);
        }

        if ($data->has(self::ATTR_CHARGE_CARD)) {
            return $this->createChargeWithCard($amount, $currency, $data->get(self::ATTR_CHARGE_CARD), $captureImmediately);
        }

        throw new \UnexpectedValueException('Incorrect data was passed. It should have been validated.');
    }

    /**
     * @param float         $amount
     * @param string        $currency
     * @param array|string  $card customerID, credit card token or credit card details
     * @param bool          $captureImmediately
     *
     * @return Charge
     */
    private function createChargeWithCard($amount, $currency, $card, $captureImmediately = false)
    {
        return $this->stripe->charges()->create([
            'currency'  => $currency,
            'amount'    => $amount,
            'source'    => $card,
            'capture'   => $captureImmediately,
        ]);
    }

    /**
     * @param      $amount
     * @param      $currency
     * @param      $customer
     * @param bool $captureImmediately
     *
     * @return Charge
     */
    private function createChargeWithCustomer($amount, $currency, $customer, $captureImmediately = false)
    {
        return $this->stripe->charges()->create([
            'currency'  => $currency,
            'amount'    => $amount,
            'customer'  => $customer,
            'capture'   => $captureImmediately,
        ]);
    }

    /**
     * @param Error\Base                    $e
     * @param FinancialTransactionInterface $transaction
     *
     * @return FinancialException
     */
    private function createFinancialException(Error\Base $e, FinancialTransactionInterface $transaction)
    {
        $exception = new FinancialException('Stripe-Response was not successful: ' . $e->getMessage(), 0, $e);
        $exception->setFinancialTransaction($transaction);

        return $exception;
    }

    /**
     * {@inheritdoc}
     */
    public function processes($paymentSystemName)
    {
        return 'quartet_payment_stripe' === $paymentSystemName;
    }
}
