<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = \Illuminate\Http\Request::create('/admin/activos?type=computer&status=&page=2', 'GET');
app()->instance('request', $request);

$controller = app('App\Http\Controllers\Sistemas_IT\ActivosController');
$response = $controller->index($request);

// The response is a view. Let's see what the view variables are!
$view = $response->original;
$dispositivos = $view->getData()['dispositivos'];

echo "TOTAL: " . $dispositivos->total() . "\n";
echo "ITEMS on PAGE 2: " . count($dispositivos->items()) . "\n";
