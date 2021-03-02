<?php

namespace Interrapidisimo\Credibanco\Controller\Checkout;

/**
 * Return Action Controller (used to handle Redirects from the Payment Gateway)
 *
 * Class Redirect
 * @package Interrapidisimo\Credibanco\Controller\Checkout
 */
class Redirect extends \Interrapidisimo\Credibanco\Controller\AbstractCheckoutRedirectAction {

    /**
     * Handle the result from the Payment Gateway
     *
     * @return void
     */
    public function execute() {
        
        switch ($this->getReturnAction()) {
            case 'success':
                $this->executeSuccessAction();
                break;

            case 'cancel':
                $this->getMessageManager()->addWarning(
                        __("You have successfully canceled your order")
                );
                $this->executeCancelAction();
                break;

            case 'failure':
                $this->getMessageManager()->addError(
                        __("Please, check your input and try again!")
                );
                $this->executeCancelAction();
                break;

            default:

                if (!empty($this->getReturnAction())) {

                    if ($this->getOrder() == null) {
                        
                        $this->redirectToCheckoutCart();
                    } else {

                        if ($this->getOrder()->getStatus() == \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW) {
                            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                            $transaction = $objectManager->create('\Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory')->create()->addOrderIdFilter($this->getOrder()->getId())->getFirstItem();
                            $transactionId = $transaction->getData('parent_txn_id');
                            
                            $orderNumber = openssl_decrypt($this->getReturnAction(), \BeGateway\Settings::$ciphering, \BeGateway\Settings::$encryptionKey, 0, \BeGateway\Settings::$cryptionV);
                            $datosOrden = [
                                "orderNumber" => \BeGateway\Settings::$prefijo.$orderNumber,                                
                                "merchantOrderNumber" => $transactionId,
                                "ip_address" => $this->getCheckoutHelper()->getUserIP(),
                                "url" => "getOrderStatus"
                            ];
                            $transaction = new \BeGateway\GetPaymentToken;
                            $responseObject = $transaction->submit($datosOrden);
                            
                            $estado = $responseObject->getOrderStatus();

                            $order = $objectManager->create('\Magento\Sales\Model\Order')->load($this->getOrder()->getId());

                            switch ($estado) {
                                case "3":
                                case "4":
                                case "6":
                                    $orderState = \Magento\Sales\Model\Order::STATE_CANCELED;
                                    $orderStatus = \Magento\Sales\Model\Order::STATE_CANCELED;
                                    $this->getMessageManager()->addError(
                                            __("Please, check your input and try again!")
                                    );
                                    $this->executeCancelAction();
                                    break;
                                case "0":
                                    $orderState = \Magento\Sales\Model\Order::STATE_CANCELED;
                                    $orderStatus = \Magento\Sales\Model\Order::STATE_CANCELED;
                                    $this->getMessageManager()->addWarning(
                                            __("You have successfully canceled your order")
                                    );
                                    $this->executeCancelAction();
                                    break;
                                case "2":
                                    $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
                                    $orderStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;
                                    $this->executeSuccessAction();
                                    break;
                                case "1":
                                case "5":
                                case "7":
                                    $orderState = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
                                    $orderStatus = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
                                    break;
                                default:
                                    $this->getMessageManager()->addError(
                                            __("Please, check your input and try again!")
                                    );
                                    $this->executeCancelAction();
                                    break;
                            }

                            $order->setState($orderState)->setStatus($orderStatus);
                            $order->save();
                        } else {
                            $this->redirectToCheckoutCart();
                        }
                    }
                } else {
                    $this->getResponse()->setHttpResponseCode(
                            \Magento\Framework\Webapi\Exception::HTTP_UNAUTHORIZED
                    );
                    $this->redirectToCheckoutCart();
                }
        }
    }

}
