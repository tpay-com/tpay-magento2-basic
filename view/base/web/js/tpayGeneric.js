require(['jquery', 'mage/translate'], function ($, $t) {
    const tos = $('.generic_accept_tos');
    const payButton = $('.tpaycom_magento2generic_submit');
    const methods = $('input[name^="payment"]')


    methods.on('click', function () {
        payButton.each(function () {
            $(this).addClass('disabled')
        })
    });

    tos.on('change', function () {
        payButton.each(function () {
            $(this).toggleClass('disabled')
        })
    });

    $(document).ready(function () {
        payButton.each(function () {
            $(this).addClass('disabled')
        })
    });
});
