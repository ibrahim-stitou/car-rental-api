<?php

error_reporting(E_ALL & ~E_USER_WARNING & ~E_USER_NOTICE);
require __DIR__ . '/vendor/autoload.php';

use OpenApi\Attributes as OA;

$openapi = \OpenApi\scan([
    __DIR__ . '/app/Core',
    __DIR__ . '/app/Modules',
]);

echo 'Paths found: ' . count((array)($openapi->paths ?? [])) . PHP_EOL;
echo 'Tags found: ' . count((array)($openapi->tags ?? [])) . PHP_EOL;

if (!empty($openapi->paths)) {
    foreach ($openapi->paths as $path) {
        echo ' - ' . $path->path . PHP_EOL;
    }
}
