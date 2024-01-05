require(['jquery', 'mage/translate'], function ($, $t) {
    function CardPayment() {
        var numberInput = $('#card_number'),
            expiryInput = $('#expiry_date'),
            cvcInput = $('#cvc'),
            RSA = $('#tpayRSA').text();

        const TRIGGER_EVENTS = 'input change blur';

        function setWrong($elem) {
            $elem.addClass('wrong').removeClass('valid');
            $("#tpaycom_magento2cards_submit").addClass('disabled');
        }

        function setValid($elem) {
            $elem.addClass('valid').removeClass('wrong');
        }

        function validateCcNumber($elem) {
            var isValid = false,
                ccNumber = $elem.val().replace(/\s/g, '').substring(0, 16),
                supported = ['mastercard', 'maestro', 'visa'],
                type = $.payment.cardType(ccNumber),
                cardTypeHolder = $('.tpay-card-icon');
            $elem.val($.payment.formatCardNumber($elem.val()));
            cardTypeHolder.attr('class', 'tpay-card-icon');
            $('div.card_icon').removeClass('hover');
            if (supported.indexOf(type) < 0 && ccNumber.length > 1) {
                $('#info_msg').css('visibility', 'visible').text($t('Sorry, your credit card type is currently not supported. Please try another payment method.'));
                setWrong($elem);
            } else if (supported.indexOf(type) > -1 && $.payment.validateCardNumber(ccNumber)) {
                setValid($elem);
                $('#info_msg').css('visibility', 'hidden');
                isValid = true;
                $('#card_vendor').val(type);
            } else {
                $('#info_msg').css('visibility', 'visible').text($t('Your credit card number seems to be invalid.'));
                setWrong($elem);
            }
            if (type !== '') {
                cardTypeHolder.addClass('tpay-' + type + '-icon');
            }
            enablePayment();

            return isValid;
        }

        function validateExpiryDate($elem) {
            var isValid = false, expiration;
            $elem.val($.payment.formatExpiry($elem.val()));
            expiration = $elem.payment('cardExpiryVal');
            if (!$.payment.validateCardExpiry(expiration.month, expiration.year)) {
                setWrong($elem);
            } else {
                setValid($elem);
                isValid = true;
            }
            enablePayment();

            return isValid;
        }

        function validateCvc($elem) {
            var isValid = false;
            if (!$.payment.validateCardCVC($elem.val(), $.payment.cardType(numberInput.val().replace(/\s/g, '')))) {
                setWrong($elem);
            } else {
                setValid($elem);
                isValid = true;
            }
            enablePayment();

            return isValid;
        }

        function enablePayment() {
            var encrypt = new JSEncrypt(),
                decoded = Base64.decode(RSA),
                encrypted,
                isValid = true,
                cn = numberInput.val().replace(/\s/g, ''),
                ed = expiryInput.val().replace(/\s/g, ''),
                cvc = cvcInput.val().replace(/\s/g, ''),
                cd = cn + '|' + ed + '|' + cvc + '|' + document.location.origin;
            $('input').each(function () {
                if ($(this).hasClass('wrong')) {
                    isValid = false;
                }
            });
            if (cn.length === 0 || ed.length === 0 || cvc.length === 0) {
                isValid = false;
            }
            if (isValid) {
                encrypt.setPublicKey(decoded);
                encrypted = encrypt.encrypt(cd);
                $("#card_data").val(encrypted);
                $("#card_short_code").val(cn.substr(-4));
                $("#tpaycom_magento2cards_submit").removeClass('disabled');
            }
        }

        numberInput.on(TRIGGER_EVENTS, function () {
            validateCcNumber($(this));
        });
        expiryInput.on(TRIGGER_EVENTS, function () {
            validateExpiryDate($(this));
        });
        cvcInput.on(TRIGGER_EVENTS, function () {
            validateCvc($(this));
        });

    }

    $(document).ready(function () {
        new CardPayment();
        $("#tpaycom_magento2cards_submit").addClass('disabled');
    });
});
