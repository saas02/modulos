<?php


namespace Interrapidisimo\Credibanco\Model\Config\Source\Locale\Currency;

/**
 * Specific Currency Source
 * Class AllSpecificCurrencies
 * @package Interrapidisimo\Credibanco\Model\Config\Source\Locale\Currency
 */
class AllSpecificCurrencies implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Builds an array for the select control in the Admin Zone
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 0,
                'label' => __('All Allowed Currencies'),
            ],
            [
                'value' => 1,
                'label' => __('Specific Currencies'),
            ]
        ];
    }
}
