magento2-tpaycom
======================

tpaycom payment gateway Magento2 extension

Install
=======

1. Go to Magento2 root folder

2. Copy plugin files to app/code/tpaycom/magento2basic

3. Enter following commands to enable module:

    ```bash
    php bin/magento module:enable tpaycom_magento2basic  
    php bin/magento setup:upgrade
    ```
4. Enable and configure module in Magento Admin under Stores/Configuration/Payment Methods/tpay.com

Composer install
=======

1. Enter following commands to download module:
    ```bash
    composer require tpaycom/magento2basic  
    ```
2. Enter following commands to enable module:

    ```bash
    php bin/magento module:enable tpaycom_magento2basic  
    php bin/magento setup:upgrade
    ```
3. Enable and configure module in Magento Admin under Stores/Configuration/Payment Methods/tpay.com


Other Notes
===========

tpaycom works with PLN only!** If PLN is not your base currency, you will not see this module on checkout pages. 

