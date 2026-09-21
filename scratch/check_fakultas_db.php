<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "--- ADMINPREVIEW ACADEMIC ---\n";
print_r(App\Support\AdminPreview::academic());
