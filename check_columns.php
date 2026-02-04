<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$columns = DB::select('DESCRIBE pedimentos');
foreach($columns as $col) {
    echo $col->Field . ' (' . $col->Type . ')' . PHP_EOL;
}