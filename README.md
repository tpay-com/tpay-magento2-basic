Magento2-Tpay
======================

Tpay payment gateway Magento2 extension

Install
=======

1. Go to Magento2 root folder

2. Copy plugin files to app/code/tpaycom/magento2basic

3. If you have already installed the [magento2cards](https://github.com/tpay-com/tpay-magento2-cards) module, you can skip this step.  
Download and copy depending library [tpay-php](https://github.com/tpay-com/tpay-php) to app/code folder. In the result your should have 2 folders in app/code - tpaycom and tpayLibs.  

4. Enter following commands to enable module:

    ```bash
    php bin/magento module:enable tpaycom_magento2basic  
    php bin/magento setup:upgrade
    ```
5. Enable and configure module in Magento Admin under Stores/Configuration/Payment Methods/tpay.com

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

