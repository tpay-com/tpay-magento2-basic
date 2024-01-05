/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.3
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery'
    ],
    function (Component, $) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'tpaycom_magento2basic/payment/tpay-generic-onsite'
            },

            afterPlaceOrder: function () {
                window.location.replace(window.checkoutConfig.tpay.payment.redirectUrl);
            },

            getLogoUrl: function (code) {
                const id = code.slice(code.indexOf('-') + 1);

                return window.checkoutConfig.generic[id].logoUrl;            },

            redirectAfterPlaceOrder: false,

            isActive: function () {
                return true;
            }
        });
    }
);
