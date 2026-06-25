<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    // Activer les sets préparés
    $rectorConfig->sets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::PHP_84,
    ]);

    // Ajouter des règles rector
    $rectorConfig->rules([
        TypedPropertyFromStrictConstructorRector::class,
        AddReturnTypeDeclarationRector::class
    ]);

    // Pour en ajouter d'autres : https://getrector.com/find-rule

    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/database',
        __DIR__ . '/routes',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/vendor',
        __DIR__ . '/storage',
    ]);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();
};
