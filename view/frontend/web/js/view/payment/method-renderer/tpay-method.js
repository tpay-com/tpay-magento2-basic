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
                template: 'tpaycom_magento2basic/payment/tpay-form'
            },

            redirectAfterPlaceOrder: false,

            getCode: function () {
                return 'tpaycom_magento2basic';
            },

            afterPlaceOrder: function () {
                window.location.replace(window.checkoutConfig.tpay.payment.redirectUrl);
            },

            showPaymentChannels: function () {
                return window.checkoutConfig.tpay.payment.showPaymentChannels;
            },

            getTerms: function () {
                return window.checkoutConfig.tpay.payment.getTerms;
            },

            getLogoUrl: function () {
                return window.checkoutConfig.tpay.payment.tpayLogoUrl;
            },

            blikStatus: function () {
                return window.checkoutConfig.tpay.payment.blikStatus;
            },

            addCSS: function () {
                return window.checkoutConfig.tpay.payment.addCSS;
            },

            getData: function () {
                var parent = this._super(),
                    paymentData = {};
                paymentData['group'] = $('#tpay-channel-input').val();
                paymentData['blik_code'] = $('#blik_code').val();
                paymentData['accept_tos'] = $('#accept_tos').is(':checked');
                return $.extend(true, parent, {'additional_data': paymentData});
            },

            isActive: function () {
                return true;
            }
        });
    }
);
