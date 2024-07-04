/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.3
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */
require(['jquery', 'mage/translate'], function ($, $t) {

        var payButton = $('#tpaycom_magento2basic_submit'),
            tos = $('#accept_tos');

        function getBankTile(groupId, groupName, logoSrc) {
            return '<div class="tpay-group-holder tpay-with-logo" id="bank-' + groupId + '">' +
                '<div class="tpay-group-name">' + groupName + '</div>' +
                '<div class="tpay-group-logo-holder">' +
                '<img src="' + logoSrc + '" class="tpay-group-logo" alt="' + groupName + '">' +
                '</div></div></div>';
        }

        function inArray(needle, haystack) {
            var length = haystack.length;
            for (var i = 0; i < length; i++) {
                if (haystack[i] == needle) return true;
            }
            return false;
        }

        function doesAmountFitToInstallments(grandTotal, channelId) {
            switch (channelId) {
                case 167: //twisto
                    return grandTotal >= 1 && grandTotal <= 1500;
                    break;
                case 169: //raty pekao
                    return grandTotal >= 100 && grandTotal <= 20000;
                    break;
                case 109:  //alior raty
                    return grandTotal >= 300 && grandTotal <= 9259;
                    break;
                case 172:  //paypo
                    return grandTotal >= 40 && grandTotal <= 3000;
                    break;
            }

            return true;
        }

        function isBrowserSupport(channelId) {
            if (channelId === 170) { //ApplePay
                return /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
            }

            return true;
        }

        function ShowChannelsCombo() {
            var str = '',
                i,
                str2 = '',
                tile,
                others = [157, 106, 109, 148, 104],
                installmentsGroupId = [109, 169, 167, 172],
                group,
                id,
                groupName,
                logoSrc,
                bank_selection_form = document.getElementById('bank-selection-form');
            for (i in tr_groups) {
                group = tr_groups[i];
                id = group['id'];
                groupName = group['name'];
                logoSrc = group['img'];

                if (window.checkoutConfig.tpay.payment.blikStatus === true && id === '150') {
                    continue;
                }

                if (inArray(id, installmentsGroupId) && !doesAmountFitToInstallments(parseFloat(window.checkoutConfig.tpay.payment.grandTotal), parseInt(id))) {
                    continue;
                }

                if (!isBrowserSupport(parseInt(id))) {
                    continue;
                }

                tile = getBankTile(id, groupName, logoSrc);

                if (inArray(id, others) === false) {
                    str += tile;
                } else {
                    str2 += tile;
                }
            }

            bank_selection_form.innerHTML = str + str2;
            $('.tpay-group-holder').each(function () {
                $(this).on('click', function () {
                    var input = $('#tpay-channel-input'),
                        active_bank_blocks = document.getElementsByClassName('tpay-active'),
                        that = $(this);
                    input.val(that.attr('id').substr(5));
                    if (active_bank_blocks.length > 0) {
                        active_bank_blocks[0].className = active_bank_blocks[0].className.replace('tpay-active', '');
                    }
                    this.className = this.className + ' tpay-active';
                    if (input.val() > 0 && $('#blik_code').val().length === 0 && tos.is(':checked')) {
                        payButton.removeClass('disabled');
                    }
                });
            });
        }

        function checkBlikInput() {
            if (window.checkoutConfig.tpay.payment.blikStatus !== true) {
                $(".blik").hide();
            }
        }

        function setBlikInputAction() {
            const TRIGGER_EVENTS = 'input change blur';

            $('#blik_code').on(TRIGGER_EVENTS, function () {
                var that = $(this);
                if (that.val().length > 0) {
                    $('#tpay-basic-main-payment').css('display', 'none');
                } else {
                    $('#tpay-basic-main-payment').css('display', 'block');
                }
                if (
                    (that.val().length === 6 || (that.val().length === 0 && $('#tpay-channel-input').val() > 0))
                    &&
                    tos.is(':checked')
                ) {
                    payButton.removeClass('disabled');
                }
                if (that.val().length > 0 && that.val().length !== 6) {
                    payButton.addClass('disabled');
                }
            });
        }

        var tr_groups = window.checkoutConfig.tpay.payment.groups;
        ShowChannelsCombo();
        checkBlikInput();
        setBlikInputAction();
        payButton.addClass('disabled');
    }
);
