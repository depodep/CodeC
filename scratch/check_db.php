<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\nCOLUMNS OF evaluation_scores:\n";
print_r(Illuminate\Support\Facades\Schema::getColumnListing('evaluation_scores'));

echo "\nCOLUMNS OF evaluation_questions:\n";
print_r(Illuminate\Support\Facades\Schema::getColumnListing('evaluation_questions'));

echo "\nDATA IN academic_periods:\n";
print_r(Illuminate\Support\Facades\DB::table('academic_periods')->get()->toArray());
