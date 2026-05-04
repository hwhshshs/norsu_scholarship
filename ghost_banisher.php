<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$emptyBatches = DB::table('billing_batch')
    ->whereNotExists(function($query) {
        $query->select(DB::raw(1))
            ->from('fees_transaction')
            ->whereRaw('fees_transaction.billing_batch_id = billing_batch.id');
    })
    ->get();

foreach ($emptyBatches as $b) {
    echo "Banishing Empty Batch: ID {$b->id}, Program {$b->program}\n";
    DB::table('billing_batch')->where('id', $b->id)->delete();
}

echo "Total banished: " . count($emptyBatches) . "\n";
