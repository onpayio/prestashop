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
    'files-whitelist' => $filesWhitelist,
    'whitelist-global-constants' => false,
    'whitelist-global-classes' => false,
    'whitelist-global-functions' => false,
];