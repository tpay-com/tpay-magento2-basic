# Magento2-Tpay

[Tpay](https://tpay.com) payment gateway Magento2 module.

[![Latest stable version](https://img.shields.io/packagist/v/tpaycom/magento2basic.svg?label=Latest%20stable%20version)](https://packagist.org/packages/tpaycom/magento2basic)
[![PHP version](https://img.shields.io/packagist/php-v/tpaycom/magento2basic.svg?label=PHP)](https://php.net)
[![License](https://img.shields.io/github/license/tpay-com/tpay-magento2-basic.svg?label=License)](LICENSE)
[![CI status](https://github.com/tpay-com/tpay-magento2-basic/actions/workflows/ci.yaml/badge.svg?branch=master)](https://github.com/tpay-com/tpay-magento2-basic/actions)
[![Type coverage](https://shepherd.dev/github/tpay-com/tpay-magento2-basic/coverage.svg)](https://shepherd.dev/github/tpay-com/tpay-magento2-basic)

[Polish version :poland: wersja polska](./README_PL.md)

## Installation

1. Execute following command to download module:
    ```console
    composer require tpaycom/magento2basic
    ```

2. Execute following commands to enable module:
    ```console
    php bin/magento module:enable Tpay_Magento2
    php bin/magento setup:upgrade
    php bin/magento setup:di:compile
    ```

3. Enable and configure module in Magento Admin under `Stores/Configuration/Payment Methods/tpay.com`.
