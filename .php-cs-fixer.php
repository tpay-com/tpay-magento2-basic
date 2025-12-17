<?php

require __DIR__.'/vendor/tpay-com/coding-standards/bootstrap.php';

$config = Tpay\CodingStandards\PhpCsFixerConfigFactory::createWithNonRiskyRules();

return $config
    ->setRules(['phpdoc_tag_no_named_arguments' => false] + $config->getRules())
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->ignoreDotFiles(false)
            ->in(__DIR__)
    );
