<?php


namespace Interrapidisimo\Credibanco\Controller\Checkout;

/**
 * Front Controller for Checkout Method
 * it does a redirect to checkout
 * Class Index
 * @package Interrapidisimo\Credibanco\Controller\Checkout
 */
class Index extends \Interrapidisimo\Credibanco\Controller\AbstractCheckoutAction
{
    /**
     * Redirect to checkout
     *
     * @return void
     */
    public function execute()
    {
        
        $order = $this->getOrder();
        
        if (isset($order)) {
            
            $redirectUrl = $this->getCheckoutSession()->getBeGatewayCheckoutRedirectUrl();

            if (isset($redirectUrl)) {
                $this->getResponse()->setRedirect($redirectUrl);
            } else {
                $this->redirectToCheckoutFragmentPayment();
            }
        }else{            
            $this->redirectToCheckoutFragmentPayment();
        }
    }
             
}
