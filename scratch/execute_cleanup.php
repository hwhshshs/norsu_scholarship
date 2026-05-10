<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- EXECUTING BILLING CLEANUP ---\n";

$targetIds = [1, 40, 42];

DB::beginTransaction();
try {
    // Delete scholars linked to these batches
    $scholarsDeleted = DB::table('billing_scholars')->whereIn('billing_batch_id', $targetIds)->delete();
    echo "Deleted $scholarsDeleted scholars from billing_scholars.\n";

    // Delete the batches themselves
    $batchesDeleted = DB::table('billing_batches')->whereIn('id', $targetIds)->delete();
    echo "Deleted $batchesDeleted batches from billing_batches.\n";

    DB::commit();
    echo "\n[SUCCESS] Cleanup complete.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n[ERROR] Cleanup failed: " . $e->getMessage() . "\n";
}
