# Moduł Płatności Tpay dla Magento 2

### Podstawowe informacje

Oficjalny moduł szybkich płatności online za pośrednictwem Tpay na platformie Magento 2.

### Funkcje

- Wiele metod płatności: e-przelew, BLIK, płatności kartowe, ratalne i odroczone
- Najwyższe standardy bezpieczeństwa: PCI DSS Level 1, szyfrowanie TLS, status KIP
- Zwroty wykonywane z poziomu panelu sklepu
- Obsługa waluty innej niż PLN za pośrednictwem kart płatniczych
- Możliwość użycia konta Sandbox (od wersji modułu: 2.0.0)

### Wymagania

- Sklep z dostepna walutą Polski Złoty (PLN)
- Program composer na serwerze
- Aktywne konto w [Tpay.com](https://tpay.com)
- Uruchomiony dostęp na koncie do Open API

#### Wersja modułu od 2.0.0

- Wersja Magento od 2.3.0
- Wersja PHP zgodna z wymaganiami platformy sprzedażowej

#### Wersja modułu do 2.0.0

- Wersja Magento od 2.0.0
- Wersja PHP zgodna z wymaganiami platformy sprzedażowej

### Instalacja modułu przez Composer

1. Pobierz moduł Tpay. W głównym folderze Magento wpisz komendę:

   ```
   composer require tpaycom/magento2basic
   ```

2. Uruchom moduł Tpay. W głównym folderze Magento wpisz komendę:

   ```
   php bin/magento module:enable Tpay_Magento2
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento setup:static-content:deploy
   ```

3. W panelu administacyjnym przejdź do konfiguracji modułu Tpay: Stores -> Configuration -> Payment Methods -> tpay.com.

### [Konfiguracja bramki płatniczej](https://support.tpay.com/pl/developer/addons/magento/instrukcja-konfiguracji-wtyczki-tpay-dla-magento-2)

### [Konfiguracja zwrotów z panelu administarcyjnego Magento2](https://support.tpay.com/pl/developer/addons/magento/instrukcja-realizacji-zwrotow-za-pomoca-wtyczki-tpay-dla-magento-2)

### [Konfiguracja osbługi waluty innej niż PLN](https://support.tpay.com/pl/developer/addons/magento/instrukcja-obslugi-wielu-walut-we-wtyczce-tpay-dla-magento-2)

### Obsługa GraphQL

Możliwa jest integracja tego rozwiązania z naszą wtyczką. Repozytorium znajdziesz
[tutaj](https://github.com/tpay-com/tpay-magento2-graphql).

### Obsługa Hyvä Checkout

Do poprawnego działania z Hyvä Checkout potrzebny jest dodatkowy moduł kompatybilności.
Repozytorium znajdziesz [tutaj](https://github.com/tpay-com/tpay-hyva-checkout)

### Wsparcie techniczne

W przypadku dodatkowych pytań zapraszamy do kontaktu z Działem Obsługi Klienta Tpay pod tym
[linkiem](https://tpay.com/kontakt)

### [Changelog](https://github.com/tpay-com/tpay-magento2-basic/releases)
