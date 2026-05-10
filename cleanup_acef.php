<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$batchIds = DB::table('billing_batches')->where('program', 'ACEF-GIAHEP')->where('ay', '2024-2025')->pluck('id');
$studentIds = DB::table('billing_scholars')->whereIn('billing_batch_id', $batchIds)->pluck('student_id')->filter();

DB::statement('SET FOREIGN_KEY_CHECKS=0;');
DB::table('students')->whereIn('id', $studentIds)->delete();
DB::table('billing_scholars')->whereIn('billing_batch_id', $batchIds)->delete();
DB::table('billing_batches')->whereIn('id', $batchIds)->delete();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');

echo "Removed " . count($studentIds) . " students and their ACEF-GIAHEP 2024-2025 batches.\n";
