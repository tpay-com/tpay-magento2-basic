# Magento2-Tpay

[Tpay](https://tpay.com) payment gateway Magento2 module.

[![Latest stable version](https://img.shields.io/packagist/v/tpaycom/magento2basic.svg?label=current%20version)](https://packagist.org/packages/tpaycom/magento2basic)
[![PHP version](https://img.shields.io/packagist/php-v/tpaycom/magento2basic.svg)](https://php.net)
[![License](https://img.shields.io/github/license/tpay-com/tpay-magento2-basic.svg)](LICENSE)
[![CI status](https://github.com/tpay-com/tpay-magento2-basic/actions/workflows/ci.yaml/badge.svg?branch=master)](https://github.com/tpay-com/tpay-magento2-basic/actions)

## Manual installation

1. Go to Magento2 root directory.

2. Copy plugin files to `app/code/tpaycom/magento2basic`.

3. If you have already installed the [`magento2cards`](https://github.com/tpay-com/tpay-magento2-cards) module, you can skip this step.
Download and copy required library [`tpay-php`](https://github.com/tpay-com/tpay-php) to `app/code` directory. In the result you should have 2 directories in `app/code` - `tpaycom` and `tpayLibs`.

4. Execute following commands to enable module:
    ```console
    php bin/magento module:enable tpaycom_magento2basic
    php bin/magento setup:upgrade
    ```

5. Enable and configure module in Magento Admin under `Stores/Configuration/Payment Methods/tpay.com`.


## Composer installation

1. Execute following command to download module:
    ```console
    composer require tpaycom/magento2basic
    ```

2. Execute following commands to enable module:
    ```console
    php bin/magento module:enable tpaycom_magento2basic
    php bin/magento setup:upgrade
    ```

3. Enable and configure module in Magento Admin under `Stores/Configuration/Payment Methods/tpay.com`.


## Other notes

This module works with PLN only! If PLN is not your base currency, you will not see this module on checkout pages.
