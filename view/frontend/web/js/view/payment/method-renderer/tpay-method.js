/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.1
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

            showChannels: function () {
                return window.checkoutConfig.tpay.payment.showPaymentChannels;
            },

            getTerms: function () {
                return window.checkoutConfig.tpay.payment.getTerms;
            },


            getLogoUrl: function () {
                return window.checkoutConfig.tpay.payment.tpayLogoUrl;
            },

            getBlikPaymentLogo: function () {
                return window.checkoutConfig.tpay.payment.getBlikPaymentLogo;
            },

            showBlikCode: function () {
                return window.checkoutConfig.tpay.payment.showBlikCode;
            },

            addCSS: function () {
                return window.checkoutConfig.tpay.payment.addCSS;
            },

            BlikInputFocus: function () {
                $("#blik_code_input").focus();
            },

            getBlikCodeInputHTML: function () {
                return window.checkoutConfig.tpay.payment.getBlikCodeInputHTML;
            },

            getData: function () {
                var parent = this._super(),
                    paymentData = {};
                paymentData['kanal'] = $('input[name="channel"]').val();
                paymentData['blik_code'] = $('input[name="blik_code"]').val();
                paymentData['akceptuje_regulamin'] = $('input[name="akceptuje_regulamin"]').val();
                return $.extend(true, parent, {'additional_data': paymentData});
            },

            isActive: function () {
                return true;
            },


        });
    }
);
