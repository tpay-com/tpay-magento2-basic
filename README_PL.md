# Magento2-Tpay

Moduł Magento2 bramki płatności [Tpay](https://tpay.com).

[![Najnowsza stabilna wersja](https://img.shields.io/packagist/v/tpaycom/magento2basic.svg?label=obecna%20wersja)](https://packagist.org/packages/tpaycom/magento2basic)
[![Wersja PHP](https://img.shields.io/packagist/php-v/tpaycom/magento2basic.svg)](https://php.net)
[![Licencja](https://img.shields.io/github/license/tpay-com/tpay-magento2-basic.svg?label=licencja)](LICENSE)
[![CI status](https://github.com/tpay-com/tpay-magento2-basic/actions/workflows/ci.yaml/badge.svg?branch=master)](https://github.com/tpay-com/tpay-magento2-basic/actions)
[![Pokrycie typami](https://shepherd.dev/github/tpay-com/tpay-magento2-basic/coverage.svg)](https://shepherd.dev/github/tpay-com/tpay-magento2-basic)

[English version :gb: wersja angielska](./README.md)

## Instalacja ręczna

1. Przejdź do katalogu głównego Magento2.

2. Skopiuj pliki wtyczki do `app/code/tpaycom/magento2basic`.

3. Jeśli masz już zainstalowany moduł [`magento2cards`](https://github.com/tpay-com/tpay-magento2-cards), możesz pominąć ten krok.
   Pobierz i skopiuj wymaganą bibliotekę [`tpay-php`](https://github.com/tpay-com/tpay-php) do katalogu `app/code`. W rezultacie powinieneś/powinnaś mieć 2 katalogi w `app/code` - `tpaycom` oraz `tpayLibs`.

4. Wykonaj następujące polecenia, aby włączyć moduł:
    ```console
    php bin/magento module:enable tpaycom_magento2basic
    php bin/magento setup:upgrade
    ```

5. Włącz i skonfiguruj moduł w panelu administratora Magento w `Stores/Configuration/Payment Methods/tpay.com`.


## Instalacja z użyciem [Composer](https://getcomposer.org)a

1. Wykonaj następujące polecenie, aby pobrać moduł:
    ```console
    composer require tpaycom/magento2basic
    ```

2. Wykonaj następujące polecenia, aby włączyć moduł:
    ```console
    php bin/magento module:enable tpaycom_magento2basic
    php bin/magento setup:upgrade
    ```

3. Włącz i skonfiguruj moduł w panelu administratora Magento w `Stores/Configuration/Payment Methods/tpay.com`.


## Notatki

Ten moduł działa tylko z PLN! Jeśli PLN nie jest Twoją podstawową walutą, nie zobaczysz tego modułu na stronach checkoutu.
