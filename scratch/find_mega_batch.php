<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Scanning for huge batches...\n";

$hugeBatches = DB::table('billing_batches')
    ->where('amount', '>', 100000000) // Over 100 Million
    ->get();

foreach ($hugeBatches as $batch) {
    echo "ID: {$batch->id} | Program: {$batch->program} | AY: {$batch->ay} | Amount: " . number_format($batch->amount, 2) . " | Scholars: {$batch->scholar_count}\n";
}

if ($hugeBatches->isEmpty()) {
    echo "No huge batches found in billing_batches table. Checking scholar records directly...\n";
}

// Also check the specific student mentioned
$fonJhon = DB::table('students')
    ->where('last_name', 'Fon')
    ->where('given_name', 'Jhon')
    ->first();

if ($fonJhon) {
    echo "\nFound Fon Jhon (ID: {$fonJhon->id})\n";
    $links = DB::table('billing_scholars')
        ->join('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
        ->where('billing_scholars.student_id', $fonJhon->id)
        ->select('billing_batches.*')
        ->get();
    
    foreach ($links as $l) {
        echo "Linked Batch - ID: {$l->id} | Program: {$l->program} | AY: {$l->ay} | Amount: " . number_format($l->amount, 2) . "\n";
    }
}
