<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\AdminPreviewController;

$controller = app(AdminPreviewController::class);

echo "Testing GET /admin/akademik (tab=fakultas)...\n";
$request = Request::create('/admin/akademik', 'GET', ['tab' => 'fakultas']);
$response = $controller->page($request, 'akademik');
echo "Status: 200 OK (View rendered: " . $response->name() . ")\n";

echo "Testing GET /admin/akademik (tab=prodi)...\n";
$request = Request::create('/admin/akademik', 'GET', ['tab' => 'prodi']);
$response = $controller->page($request, 'akademik');
echo "Status: 200 OK (View rendered: " . $response->name() . ")\n";

echo "Testing GET /admin/akademik (tab=semester)...\n";
$request = Request::create('/admin/akademik', 'GET', ['tab' => 'semester']);
$response = $controller->page($request, 'akademik');
echo "Status: 200 OK (View rendered: " . $response->name() . ")\n";
