<?php
include 'vendor/autoload.php';
$app = include 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$sid = 21; // Jimson Glacita

DB::beginTransaction();
try {
    // Delete transactions
    DB::table('fees_transaction')->where('stdid', $sid)->delete();
    DB::table('disbursed_transaction')->where('stdid', $sid)->delete();
    
    // Delete student record
    DB::table('student')->where('id', $sid)->delete();

    DB::commit();
    echo "Successfully removed Jimson Glacita (ID: $sid) and all associated records.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error removing records: " . $e->getMessage() . "\n";
}
