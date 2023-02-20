<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

$filesWhitelist = [];
$randomCompatLibPath = 'vendor/paragonie/random_compat/lib/';
foreach(scandir($randomCompatLibPath) as $file) {
    if (!in_array($file, ['.', '..'])) {
        $filesWhitelist[] = $randomCompatLibPath . $file;
    }
}

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