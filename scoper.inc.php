<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

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
    'whitelist-global-constants' => false,
    'whitelist-global-classes' => false,
    'whitelist-global-functions' => false,
];