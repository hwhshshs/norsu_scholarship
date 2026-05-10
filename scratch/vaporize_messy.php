<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$messy = DB::table('billing_scholars')
    ->where('student_name', 'like', '%student_id%')
    ->orWhere('student_name', 'like', '%2022%') // Catch the ones with concatenated IDs
    ->get();

echo "Found " . $messy->count() . " messy records.\n";

foreach($messy as $m) {
    echo "Deleting record ID: " . $m->id . " from Batch: " . $m->billing_batch_id . " | Content: " . substr($m->student_name, 0, 30) . "...\n";
    DB::table('billing_scholars')->where('id', $m->id)->delete();
    DB::table('billing_batches')->where('id', $m->billing_batch_id)->decrement('scholar_count');
}

echo "Vaporization complete.\n";
