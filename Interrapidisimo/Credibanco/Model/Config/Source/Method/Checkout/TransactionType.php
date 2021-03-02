<?php


namespace Interrapidisimo\Credibanco\Model\Config\Source\Method\Checkout;

use \Interrapidisimo\Credibanco\Helper\Data as DataHelper;

/**
 * Checkout Transaction Types Model Source
 * Class TransactionType
 * @package Interrapidisimo\Credibanco\Model\Config\Source\Method\Checkout
 */
class TransactionType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Builds the options for the MultiSelect control in the Admin Zone
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => DataHelper::AUTHORIZE,
                'label' => __('Authorize'),
            ],
            [
                'value' => DataHelper::PAYMENT,
                'label' => __('Payment'),
            ],
            [
                'value' => DataHelper::CREDIT_CARD,
                'label' => __('Bankcard'),
            ],
            [
                'value' => DataHelper::CREDIT_CARD_HALVA,
                'label' => __('Halva bankcard'),
            ],
            [
                'value' => DataHelper::ERIP,
                'label' => __('ERIP'),
            ]
        ];
    }
}
