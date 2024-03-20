# Magento2-Tpay

Moduł Magento2 bramki płatności [Tpay](https://tpay.com).

[![Najnowsza stabilna wersja](https://img.shields.io/packagist/v/tpaycom/magento2basic.svg?label=Najnowsza%20stabilna%20wersja)](https://packagist.org/packages/tpaycom/magento2basic)
[![Wersja PHP](https://img.shields.io/packagist/php-v/tpaycom/magento2basic.svg?label=PHP)](https://php.net)
[![Licencja](https://img.shields.io/github/license/tpay-com/tpay-magento2-basic.svg?label=Licencja)](LICENSE)
[![CI status](https://github.com/tpay-com/tpay-magento2-basic/actions/workflows/ci.yaml/badge.svg?branch=master)](https://github.com/tpay-com/tpay-magento2-basic/actions)
[![Pokrycie typami](https://shepherd.dev/github/tpay-com/tpay-magento2-basic/coverage.svg)](https://shepherd.dev/github/tpay-com/tpay-magento2-basic)

[English version :gb: wersja angielska](./README.md)

## Instalacja

1. Wykonaj następujące polecenie, aby pobrać moduł:
    ```console
    composer require tpaycom/magento2basic
    ```

2. Wykonaj następujące polecenia, aby włączyć moduł:
    ```console
    php bin/magento module:enable Tpay_Magento2
    php bin/magento setup:upgrade
    php bin/magento setup:di:compile
    ```

3. Włącz i skonfiguruj moduł w panelu administratora Magento w `Stores/Configuration/Payment Methods/tpay.com`.
