/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.2
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */
require(['jquery', 'mage/translate'], function ($, $t) {

    function renderForm() {
        var cards = generateHtml();
        if (cards === undefined) {
            $('#card_form').css('display', 'block');
            $('#saved_card_payment').css('display', 'none');
            return;
        }

        $('input[name=savedId]').each(function () {
            if ($(this).val() !== 'new') {
                $(this).click(function () {
                    if ($(this).is(":checked") && $('#card_accept_tos').is(':checked')) {
                        $('#card_form').css({opacity: 1.0}).animate({opacity: 0.0}, 500);
                        setTimeout(
                            function () {
                                $('#card_form').css({display: "none"})
                            }, 500
                        );
                        $("#tpaycom_magento2cards_submit").removeClass('disabled');
                    }
                });
            }
        });

        $('#newCard').click(function () {
            if ($(this).is(":checked")) {
                $('#card_form').css({opacity: 0.0, display: "block"}).animate({opacity: 1.0}, 500);
                var x = false, cn = $('#card_number').val(), ed = $('#expiry_date').val(), cvc = $('#cvc').val();
                $('input').each(function () {
                    if ($(this).hasClass('wrong')) {
                        x = true;
                    }
                });
                if (cn.length === 0 || ed.length === 0 || cvc.length === 0) {
                    x = true;
                }
                if (x) {
                    $("#tpaycom_magento2cards_submit").addClass('disabled');
                }
            }
        });
    }

    function generateHtml() {
        var userTokens = window.checkoutConfig.tpaycards.payment.customerTokens,
            divContent = '',
            text = $t('Pay with saved card ');

        if (userTokens.length === 0) {
            return;
        }

        for (var i = 0; i < userTokens.length; i++) {
            var card = userTokens[i];
            var cardCode = card.cardShortCode, cardId = card.id;
            var vendor = card.vendor;
            var img = '<img id="saved_icon" class="tpay-'+ vendor +'-icon"/>';
            divContent += ('<input type="radio" name="savedId" id="cardN' + cardId + '" value="' + cardId + '"/>');
            divContent += ('<label for="cardN' + cardId + '" name="' + vendor + '">' + text.concat(cardCode) + img + '</label><br/>');
        }

        $('#saved_card_payment').prepend(divContent);
        $('input[name=savedId]').first().prop('checked', "checked");
        return divContent;
    }

    $('document').ready(function () {
        renderForm();
    });
});
