<?php

require __DIR__.'/vendor/autoload.php';

$config = Tpay\CodingStandards\PhpCsFixerConfigFactory::createWithLegacyRules()
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->ignoreDotFiles(false)
            ->in(__DIR__.'/..')
    );

$rules = $config->getRules();

$rules['nullable_type_declaration_for_default_null_value'] = false;

return $config->setRules($rules);
