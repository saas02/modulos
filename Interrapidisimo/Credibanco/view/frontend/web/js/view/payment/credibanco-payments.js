
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        rendererList.push(
            {
                type: 'credibanco_checkout',
                component: 'Interrapidisimo_Credibanco/js/view/payment/method-renderer/checkout-method'
            },
            {
                type: 'credibanco_direct',
                component: 'Interrapidisimo_Credibanco/js/view/payment/method-renderer/direct-method'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
