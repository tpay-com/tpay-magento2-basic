/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.3
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 *//*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component,
              rendererList) {
        'use strict';
        rendererList.push(
            {
                type: 'Tpay_Magento2',
                component: 'Tpay_Magento2/js/view/payment/method-renderer/tpay-method'
            }
        );

         Object.values(window.checkoutConfig.generic).forEach((element) => rendererList.push({type: `generic-${element.id}`, component: 'Tpay_Magento2/js/view/payment/method-renderer/tpay-generic-onsite'}))


        rendererList.push({type: 'Tpay_Magento2_Cards', component: 'Tpay_Magento2/js/view/payment/method-renderer/tpay-card-method'});

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
