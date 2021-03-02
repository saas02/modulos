<?php


namespace Interrapidisimo\Credibanco\Controller\Direct;

/**
 * Return Action Controller (used to handle Redirects from the Payment Gateway)
 *
 * Class Redirect
 * @package Interrapidisimo\Credibanco\Controller\Direct
 */
class Redirect extends \Interrapidisimo\Credibanco\Controller\AbstractCheckoutRedirectAction
{
    /**
     * Handle the result from the Payment Gateway
     *
     * @return void
     */
    public function execute()
    {
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
                /**
                 * If the customer is redirected here after processing Server to Server 3D-Secure transaction
                 * this mean the Payment Transaction Status has been set to "Pending Async".
                 * So there should be a problem with the 3-D Secure Code Authentication, but the
                 * exact error message from the payment gateway will be delivered after processing the
                 * notification from the gateway
                 */
                $this->getMessageManager()->addError(
                    __('Please, check if the used card supports 3-D Secure and you have entered ' .
                       'a valid 3-D Secure code! Please try again!')
                );
                $this->executeCancelAction();
                break;

            default:
                $this->getResponse()->setHttpResponseCode(
                    \Magento\Framework\Webapi\Exception::HTTP_UNAUTHORIZED
                );
        }
    }
}
