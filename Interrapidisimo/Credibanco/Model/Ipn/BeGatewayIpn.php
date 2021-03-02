<?php

namespace Interrapidisimo\Credibanco\Model\Ipn;

/**
 * Checkout Method IPN Handler Class
 * Class CheckoutIpn
 * @package Interrapidisimo\Credibanco\Model\Ipn
 */
class BeGatewayIpn extends \Interrapidisimo\Credibanco\Model\Ipn\AbstractIpn
{
    /**
     * @return string
     */
    protected function getPaymentMethodCode()
    {
        return \Interrapidisimo\Credibanco\Model\Method\Checkout::CODE;
    }

    /**
     * Update Pending Transactions and Order Status
     * @param \stdClass $responseObject
     * @throws \Exception
     */
    protected function processNotification($responseObject)
    {
        $payment = $this->getPayment();
        $helper = $this->getModuleHelper();

        $this->getModuleHelper()->updateTransactionAdditionalInfo(
            $responseObject->getUid(),
            $responseObject,
            true
        );

        if (isset($responseObject->getResponse()->transaction)) {
            $payment_transaction = $responseObject;

            $payment
                ->setLastTransId(
                    $payment_transaction->getUid()
                )
                ->setTransactionId(
                    $payment_transaction->getUid()
                )
                ->setParentTransactionId(
                    isset(
                      $responseObject->getResponse()->transaction->parent_uid
                    ) ?
                      $responseObject->getResponse()->transaction->parent_uid
                      : null
                )
                ->setIsTransactionPending(
                    $this->getShouldSetCurrentTranPending(
                        $payment_transaction
                    )
                )
                ->setShouldCloseParentTransaction(
                    true
                )
                ->setIsTransactionClosed(
                    $this->getShouldCloseCurrentTransaction(
                        $payment_transaction
                    )
                )
                ->setPreparedMessage(
                    $this->createIpnComment(
                        $payment_transaction->getMessage()
                    )
                )
                ->resetTransactionAdditionalInfo(

                );

            $this->getModuleHelper()->setPaymentTransactionAdditionalInfo(
                $payment,
                $payment_transaction
            );

            $money = new \BeGateway\Money;
            $money->setCents($payment_transaction->getResponse()->transaction->amount);
            $money->setCurrency($payment_transaction->getResponse()->transaction->currency);

            switch ($payment_transaction->getResponse()->transaction->type) {
                case $helper::AUTHORIZE:
                    $payment->registerAuthorizationNotification($money->getAmount());
                    break;
                case $helper::PAYMENT:
                    $payment->registerCaptureNotification($money->getAmount());
                    break;
                default:
                    break;
            }

            //if (!$this->getOrder()->getEmailSent()) {
            //    $this->_orderSender->send($this->getOrder());
            //}

            $payment->save();
        }

        $this->getModuleHelper()->setOrderState(
            $this->getOrder(),
            $responseObject->getStatus()
        );
    }
}
