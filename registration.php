<?php

use Composer\InstalledVersions;

Magento\Framework\Component\ComponentRegistrar::register(
    Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Tpay_Magento2',
    __DIR__
);

if (class_exists(InstalledVersions::class)) {
    Magento\Framework\Component\ComponentRegistrar::register(
        Magento\Framework\Component\ComponentRegistrar::LIBRARY,
        'tpay-com/tpay-openapi-php',
        InstalledVersions::getInstallPath('tpay-com/tpay-openapi-php')
    );
} else {
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
}
