# Tpay Payment Module for Magento 2

### [Polish version of README](https://github.com/tpay-com/tpay-magento2-basic/blob/master/README_PL.md)

### Basic information

The official module for quick online payments via Tpay on the Magento 2 platform.

### Functions

- Many payment methods: e-transfer, BLIK, card payments, installments and buy now pay later.
- The highest security standards: PCI DSS Level 1, TLS encryption, KIP status
- Returns made from the admin store panel
- Support for currencies other than PLN via payment cards
- Possibility to use a Sandbox account (from module version: 2.0.0)

### Requirements

- Shop with available currency: Polish Zloty (PLN)
- Composer on the server
- Active account at [Tpay.com](https://tpay.com)
- Account access to Open API enabled

#### Module version from 2.0.0

- Magento version from 2.3.0
- PHP version compliant with the requirements of the sales platform

#### Module version up to 2.0.0

- Magento version from 2.0.0
- PHP version compliant with the requirements of the sales platform

### Module installation via Composer

1. Download the Tpay module. In the main Magento folder, enter the command:

   ```
   composer require tpaycom/magento2basic
   ```

2. Turn on the Tpay module. In the main Magento folder, enter the command:

   ```
   php bin/magento module:enable Tpay_Magento2
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento setup:static-content:deploy
   ```

3. Configure module in admin panel: Stores -> Configuration -> Payment Methods -> tpay.com.

### [Payment gateway configuration](https://support.tpay.com/pl/developer/addons/magento/instrukcja-konfiguracji-wtyczki-tpay-dla-magento-2)

### [Configuration of returns from the administration panel](https://support.tpay.com/pl/developer/addons/magento/instrukcja-realizacji-zwrotow-za-pomoca-wtyczki-tpay-dla-magento-2)

### [Configuration of support for currencies other than PLN](https://support.tpay.com/pl/developer/addons/magento/instrukcja-obslugi-wielu-walut-we-wtyczce-tpay-dla-magento-2)

### GraphQL support

It is possible to integrate this solution with our plugin. You can find the repository
[here](https://github.com/tpay-com/tpay-magento2-graphql).


### Hyvä Checkout support

If you are using Hyvä Checkout additional compatybility module is required.
You can find the repository [here](https://github.com/tpay-com/tpay-hyva-checkout)

### Technical assistance

If you have additional questions, please contact the Tpay Customer Service Department at this
[link](https://tpay.com/kontakt)

### [Changelog](https://github.com/tpay-com/tpay-magento2-basic/releases)
