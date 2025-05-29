<?php

Magento\Framework\Component\ComponentRegistrar::register(
    Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Tpay_Magento2',
    __DIR__
);

foreach (Composer\Autoload\ClassLoader::getRegisteredLoaders() as $loader) {
    $path = $loader->findFile(Tpay\OpenApi\Api\TpayApi::class);
    if ($path) {
        Magento\Framework\Component\ComponentRegistrar::register(
            Magento\Framework\Component\ComponentRegistrar::LIBRARY,
            'tpay-com/tpay-openapi-php',
            dirname($path, 2)
        );

        return;
    }
}
