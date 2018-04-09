/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.1
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */
require(['jquery', 'mage/translate'], function ($, $t) {

    function ShowChannelsCombo() {
        var title  = $t('Choose payment method');
        var str = '<p><strong>' + title + ':</strong></p><div id="kanal"></div>';

        for (var i = 0; i < tr_channels.length; i++) {
            if (addChannelToList(tr_channels[i]) === true) {
                str += '<div   class="channel"   ><image id="' + tr_channels[i][0] + '" class="check"  src="' + tr_channels[i][3] + '" ></div>';
            }
        }

        var container = jQuery("#channels");
        container.append(str);

        jQuery(".check").click(function () {

            $(".check").parent().removeClass("checked");
            $(this).parent().addClass("checked");

            var kanal = $(this).attr("id");
            $('#channel').val(kanal);

            var blik = showBlikInput(kanal);

            if (!blik) {
                $("html,body").animate({scrollTop: $('body').height() - 150}, 600);
            }
        });
    }

    function showBlikInput(kanal) {
        if (window.checkoutConfig.tpay.payment.blikStatus !== true) {
            return false;
        }
        $(".blik").hide();

        if (kanal === window.checkoutConfig.tpay.payment.getBlikChannelID) {
            $(".blik").show();
            $("html,body").animate({scrollTop: 0}, 600);
            return true;
        }
        return false;
    }

    function showOnlyOnlinePayments() {
        if (window.checkoutConfig.tpay.payment.onlyOnlineChannels !== true) {
            return '0';
        }
        return '1';
    }

    function addChannelToList(tr_channels) {
        if (window.checkoutConfig.tpay.payment.getInstallmentsAmountValid === false && tr_channels[0] === '49') {
            return false;
        }

        if (showOnlyOnlinePayments() === '0') {
            return true;
        }

        if (showOnlyOnlinePayments() === '1' && tr_channels[2] === '1') {
            return true;
        }

        return false;
    }

    function CheckBlikLevelZeroAsDefault() {
        if (window.checkoutConfig.tpay.payment.blikStatus !== true) {
            $(".blik").hide();
            return false;
        }
        var blik_id = window.checkoutConfig.tpay.payment.getBlikChannelID;
        $('#' + blik_id).parent().addClass("checked");
        $('#channel').val(blik_id);
        $(".blik").show();

    }

    jQuery.getScript("https://secure.tpay.com/channels-" + window.checkoutConfig.tpay.payment.merchantId + showOnlyOnlinePayments() + ".js", function () {
        ShowChannelsCombo();
        CheckBlikLevelZeroAsDefault()
    });
});
