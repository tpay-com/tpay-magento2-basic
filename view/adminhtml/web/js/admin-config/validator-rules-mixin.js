define([
    'jquery'
], function ($) {
    'use strict';
    return function (target) {
        $.validator.addMethod(
            'validate-under-thirty',
            function (value) {
                return 1 <= value && value <= 30;
            },
            $.mage.__('Please enter value between 1 and 30')
        );
        return target;
    };
});
