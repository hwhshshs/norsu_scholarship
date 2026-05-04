<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// 1. Force Ian's full name everywhere
DB::table('student')->where('id', 40)->update([
    'given_name' => 'Ian Christian',
    'last_name' => 'Maranga',
    'sname' => 'Maranga, Ian Christian'
]);
echo "Updated Ian's name to Ian Christian Maranga.\n";

// 2. Find and DELETE the "No names" batch row
// It shows 1 Grantee, so there is a fees_transaction pointing to something.
$noNameBatches = DB::table('billing_batch as bb')
    ->select('bb.id')
    ->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
            ->from('fees_transaction as ft')
            ->join('student as s', 's.id', '=', 'ft.stdid')
            ->whereRaw('ft.billing_batch_id = bb.id')
            ->whereRaw("COALESCE(s.given_name, '') <> ''");
    })
    ->get();

foreach ($noNameBatches as $batch) {
    echo "Deleting orphan batch ID: {$batch->id}\n";
    DB::table('fees_transaction')->where('billing_batch_id', $batch->id)->delete();
    DB::table('disbursed_transaction')->where('billing_batch_id', $batch->id)->delete();
    DB::table('billing_batch')->where('id', $batch->id)->delete(); // Hard delete
}

// 3. Double check Ian's transaction link
$ianTx = DB::table('fees_transaction')->where('stdid', 40)->first();
if ($ianTx) {
    echo "Ian (ID 40) is linked to Batch ID: {$ianTx->billing_batch_id}\n";
} else {
    echo "WARNING: Ian (ID 40) has no billing transaction!\n";
}
