<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// 1. Rename the existing active record (ID 42)
DB::table('student')->where('id', 42)->update([
    'given_name' => 'Ian Christian',
    'last_name' => 'Maranga',
    'sname' => 'Maranga, Ian Christian'
]);
echo "Corrected name for ID 42 to Ian Christian Maranga.\n";

// 2. Final check for any "No names" orphans one last time
$orphans = DB::table('billing_batch')->where('delete_status', '0')->get();
foreach ($orphans as $b) {
    $count = DB::table('fees_transaction')->where('billing_batch_id', $b->id)->count();
    if ($count == 0) {
        echo "Hard-deleting empty batch ID: {$b->id}\n";
        DB::table('billing_batch')->where('id', $b->id)->delete();
    }
}
