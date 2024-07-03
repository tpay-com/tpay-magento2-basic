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
                template: 'Tpay_Magento2/payment/tpay-generic-onsite'
            },

            afterPlaceOrder: function () {
                window.location.replace(window.checkoutConfig.tpay.payment.redirectUrl);
            },

            getTerms: function () {
                return window.checkoutConfig.tpay.payment.getTerms;
            },

            getRegulations: function () {
                return window.checkoutConfig.tpaycards.payment.getRegulations;
            },

            getLogoUrl: function (code) {
                const id = code.slice(code.indexOf('-') + 1);

                return window.checkoutConfig.generic[id].logoUrl;            },

            redirectAfterPlaceOrder: false,

            getData: function () {
                var parent = this._super(),
                    paymentData = {};

                paymentData['accept_tos'] = $('input[name="accept_tos"]').is(':checked');

                return $.extend(true, parent, {'additional_data': paymentData});
            },

            isActive: function () {
                return true;
            }
        });
    }
);
