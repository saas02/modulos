<?php


namespace Interrapidisimo\Credibanco\Block\Adminhtml\System\Config\Fieldset;

/**
 * Renderer for beGateway Checkout Panel in System Configuration
 *
 * Class CheckoutPayment
 * @package Interrapidisimo\Credibanco\Block\Adminhtml\System\Config\Fieldset
 */
class CheckoutPayment extends \Interrapidisimo\Credibanco\Block\Adminhtml\System\Config\Fieldset\Base\Payment
{
    /**
     * Retrieves the Module Panel Css Class
     * @return string
     */
    protected function getBlockHeadCssClass()
    {
        return "BeGatewayCheckout";
    }
}
