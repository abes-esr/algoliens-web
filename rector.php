<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/src',
    ])
    // uncomment to reach your current PHP version
    // ->withPhpSets()
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
    ])
    ->withSets([
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_51,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_52,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_53,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_54,
        \Rector\Symfony\Set\SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES
    ]);
