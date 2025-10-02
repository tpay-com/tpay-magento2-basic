<?php

use Composer\InstalledVersions;

if (class_exists(InstalledVersions::class)) {
    try {
        $path = InstalledVersions::getInstallPath('tpay-com/tpay-openapi-php');

        Magento\Framework\Component\ComponentRegistrar::register(
            Magento\Framework\Component\ComponentRegistrar::MODULE,
            'Tpay_Magento2',
            __DIR__
        );

        Magento\Framework\Component\ComponentRegistrar::register(
            Magento\Framework\Component\ComponentRegistrar::LIBRARY,
            'tpay-com/tpay-openapi-php',
            $path.'/src'
        );
    } catch (OutOfBoundsException $e) {
    }
}
