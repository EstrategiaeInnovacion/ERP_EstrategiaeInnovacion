<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Illuminate\Pagination\Paginator::currentPageResolver(function () {
    return 2;
});

$activos = app('App\Services\ActivosDbService');
$res = $activos->getAllDevicesPaginated(null, 'computer', null, 15);
echo "TOTAL: " . $res->total() . "\n";
echo "ITEMS: " . count($res->items()) . "\n";
