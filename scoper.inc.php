<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

$filesWhitelist = [];

return [
    'prefix' => 'PrestashopOnpay',
    'finders' => [
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->in('vendor'),
        Finder::create()->append([
            'composer.json',
        ]),
    ],
    'exclude-files' => $filesWhitelist,
    'expose-global-constants' => false,
    'expose-global-classes' => false,
    'expose-global-functions' => false,
];