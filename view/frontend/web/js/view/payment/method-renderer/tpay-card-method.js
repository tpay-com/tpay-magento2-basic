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
                template: 'Tpay_Magento2/payment/card-tpay-form'
            },

            redirectAfterPlaceOrder: false,

            getCode: function () {
                return 'Tpay_Magento2_Cards';
            },

            afterPlaceOrder: function () {
                $("#card_number").val('');
                $("#cvc").val('');
                $("#expiry_date").val('');
                $("#loading_scr").fadeIn();
                window.location.replace(window.checkoutConfig.tpaycards.payment.redirectUrl);
            },

            getTerms: function () {
                return window.checkoutConfig.tpaycards.payment.getTerms;
            },

            getRegulations: function () {
                return window.checkoutConfig.tpaycards.payment.getRegulations;
            },

            cardFetchJavaScripts: function () {
                return window.checkoutConfig.tpaycards.payment.fetchJavaScripts;
            },

            cardGetRSAkey: function () {
                return window.checkoutConfig.tpaycards.payment.getRSAkey;
            },

            cardGetLogoUrl: function () {
                return window.checkoutConfig.tpaycards.payment.tpayCardsLogoUrl;
            },

            cardGetTpayLoadingGif: function () {
                return window.checkoutConfig.tpaycards.payment.getTpayLoadingGif;
            },

            cardAddCSS: function () {
                return window.checkoutConfig.tpaycards.payment.addCSS;
            },

            cardShowSaveBox: function () {
                if (window.checkoutConfig.tpaycards.payment.isCustomerLoggedIn
                    && window.checkoutConfig.tpaycards.payment.isSavingEnabled) {
                    $('#tpay-card-save-checkbox').css('display', 'block');
                }
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

                paymentData['card_data'] = $('input[name="card_data"]').val();
                paymentData['card_save'] = $('input[name="card_save"]').is(":checked");
                paymentData['card_id'] = savedId;
                paymentData['card_vendor'] = $('input[name="card_vendor"]').val();
                paymentData['short_code'] = $('input[name="card_short_code"]').val();

                return $.extend(true, parent, {'additional_data': paymentData});
            },

            isActive: function () {
                return true;
            },
        });
    }
);
