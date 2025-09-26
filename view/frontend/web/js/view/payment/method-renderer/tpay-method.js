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

            blikCodeChanged: function (obj, event) {
                if (event.originalEvent) {
                    $(event.target).val($(event.target).val().replaceAll(/\D/g, ''));
                }
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
                return window.checkoutConfig.tpay.payment.getRegulations;
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
                paymentData['accept_tos'] = true;
                paymentData['channel'] = "";

                if ($('#blik_alias').prop('checked')) {
                    paymentData['blik_alias'] = $('#blik_alias').val();
                }

                return $.extend(true, parent, {'additional_data': paymentData});
            },

            isActive: function () {
                return true;
            },
        });
    }
);
