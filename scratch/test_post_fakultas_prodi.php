<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\AdminPreviewController;
use App\Models\Fakultas;
use App\Models\Prodi;

$controller = app(AdminPreviewController::class);
view()->share('errors', new \Illuminate\Support\ViewErrorBag);

echo "1. Simulating POST /admin/akademik to add new Fakultas 'FK' (Fakultas Kedokteran)...\n";
$reqFak = Request::create('/admin/akademik', 'POST', [
    'type' => 'fakultas',
    'code' => 'FK',
    'name' => 'Fakultas Kedokteran',
    'status' => 'aktif',
]);
$resFak = $controller->academic($reqFak);
echo "Redirect to: " . $resFak->getTargetUrl() . "\n";

$dbFak = Fakultas::where('code', 'FK')->first();
if ($dbFak) {
    echo "SUCCESS: Fakultas FK persisted in database! ID: {$dbFak->id}, Name: {$dbFak->name}\n";
} else {
    echo "FAILED: Fakultas FK not found in database!\n";
}

echo "\n2. Simulating POST /admin/akademik to add new Prodi 'PD' (Pendidikan Dokter) linked to parent Fakultas FK...\n";
// Find FK id in AdminPreview::academic()
$academic = App\Support\AdminPreview::academic();
$fkAcademicId = null;
foreach ($academic as $id => $item) {
    if (($item['type'] ?? '') === 'fakultas' && $item['code'] === 'FK') {
        $fkAcademicId = $id;
        break;
    }
}
echo "FK Academic ID found: {$fkAcademicId}\n";

$reqProdi = Request::create('/admin/akademik', 'POST', [
    'type' => 'prodi',
    'code' => 'PD',
    'name' => 'S1 Pendidikan Dokter',
    'parent' => $fkAcademicId,
    'status' => 'aktif',
]);
$resProdi = $controller->academic($reqProdi);
echo "Redirect to: " . $resProdi->getTargetUrl() . "\n";

$dbProdi = Prodi::with('fakultas')->where('code', 'PD')->first();
if ($dbProdi && $dbProdi->fakultas_id && $dbProdi->fakultas?->code === 'FK') {
    echo "SUCCESS: Prodi PD persisted in database with parent Fakultas FK (ID: {$dbProdi->fakultas_id})!\n";
} else {
    echo "FAILED: Prodi PD not correctly linked to Fakultas in database! Data: " . json_encode($dbProdi) . "\n";
}

echo "\n3. Testing view render with newly added data...\n";
$reqViewProdi = Request::create('/admin/akademik', 'GET', ['tab' => 'prodi']);
$viewRes = $controller->page($reqViewProdi, 'akademik');
$viewHtml = $viewRes->render();
if (str_contains($viewHtml, 'Pendidikan Dokter') && str_contains($viewHtml, 'Fakultas Kedokteran (FK)')) {
    echo "SUCCESS: Program Studi table visibly renders 'Pendidikan Dokter' and parent faculty badge 'Fakultas Kedokteran (FK)'!\n";
} else {
    echo "FAILED: View does not contain expected prodi or parent faculty!\n";
}
