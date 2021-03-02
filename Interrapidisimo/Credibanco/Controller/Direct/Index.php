<?php


namespace Interrapidisimo\Credibanco\Controller\Direct;

/**
 * Front Controller for Direct Method
 * it redirects to the 3D-Secure Form when applicable
 * Class Index
 * @package Interrapidisimo\Credibanco\Controller\Direct
 */
class Index extends \Interrapidisimo\Credibanco\Controller\AbstractCheckoutAction
{
    /**
     * Redirect to the 3-D Secure Form or to the Final Checkout Success Page
     *
     * @return void
     */
    public function execute()
    {
        $order = $this->getOrder();

        if (isset($order)) {
            $redirectUrl = $this->getCheckoutSession()->getBeGatewaygCheckoutRedirectUrl();

            if (isset($redirectUrl)) {
                $this->getCheckoutSession()->setBeGatewayCheckoutRedirectUrl(null);
                $this->getResponse()->setRedirect($redirectUrl);
            } else {
                $this->redirectToCheckoutOnePageSuccess();
            }
        }
    }
}
