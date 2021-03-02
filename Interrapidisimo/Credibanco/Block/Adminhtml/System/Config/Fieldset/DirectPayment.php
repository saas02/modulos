<?php


namespace Interrapidisimo\Credibanco\Block\Adminhtml\System\Config\Fieldset;

/**
 * Renderer for BeGateway Panel in System Configuration
 *
 * Class DirectPayment
 * @package Interrapidisimo\Credibanco\Block\Adminhtml\System\Config\Fieldset
 */
class DirectPayment extends \Interrapidisimo\Credibanco\Block\Adminhtml\System\Config\Fieldset\Base\Payment
{
    /**
     * Retrieves the Module Panel Css Class
     * @return string
     */
    protected function getBlockHeadCssClass()
    {
        return "BeGatewayDirect";
    }
}
