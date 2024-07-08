require(['jquery', 'mage/translate'], function ($, $t) {
    const payButton = $('.tpaycom_magento2generic_submit');
    const methods = $('input[name^="payment"]')

    methods.on('click', function () {
        payButton.each(function () {
            $(this).addClass('disabled')
        })
    });

    $(document).ready(function () {
        payButton.each(function () {
            $(this).addClass('disabled')
        })
    });
});
