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
        'jquery',
        'Magento_Checkout/js/model/totals'
    ],
    function (Component, $) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Tpay_Magento2/payment/tpay-form'
            },

            redirectAfterPlaceOrder: false,

            getCode: function () {
                return 'Tpay_Magento2';
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

            getRegulations: function () {
                return window.checkoutConfig.tpaycards.payment.getRegulations;
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
                var savedId = 'new';
                $('input[id^=cardN]').each(function () {
                    if ($(this).is(":checked")) {
                        savedId = $(this).val();
                    }
                });
                var parent = this._super(),
                    paymentData = {};
                paymentData['group'] = $('#tpay-channel-input').val();
                paymentData['blik_code'] = $('#blik_code').val();
                paymentData['accept_tos'] = $('input[name="accept_tos"]').is(':checked');
                paymentData['channel'] = "";

                return $.extend(true, parent, {'additional_data': paymentData});
            },

            isActive: function () {
                return true;
            },
        });
    }
);
