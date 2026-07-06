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
                type: 'atoa_card',
                component: 'Atoa_AtoaPayment/js/view/payment/method-renderer/atoa'
            }
        );
        return Component.extend({});
    }
);
