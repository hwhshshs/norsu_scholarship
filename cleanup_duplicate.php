<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$idToDelete = 27;
$batchId = 6;

$deleted = DB::table('billing_scholars')->where('id', $idToDelete)->delete();

if ($deleted) {
    DB::table('billing_batches')->where('id', $batchId)->decrement('scholar_count');
    echo "Successfully deleted entry $idToDelete and updated batch $batchId scholar count.\n";
} else {
    echo "Entry $idToDelete not found or already deleted.\n";
}
