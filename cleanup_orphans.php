<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Find fees_transactions where student doesn't exist
$orphans = DB::table('fees_transaction as ft')
    ->leftJoin('student as s', 's.id', '=', 'ft.stdid')
    ->whereNull('s.id')
    ->select('ft.*')
    ->get();

foreach ($orphans as $ft) {
    echo "Deleting orphan fees_transaction ID {$ft->id} (points to missing student ID {$ft->stdid})\n";
    DB::table('fees_transaction')->where('id', $ft->id)->delete();
}

// Find disbursed_transactions where student doesn't exist
$orphansD = DB::table('disbursed_transaction as dt')
    ->leftJoin('student as s', 's.id', '=', 'dt.stdid')
    ->whereNull('s.id')
    ->select('dt.*')
    ->get();

foreach ($orphansD as $dt) {
    echo "Deleting orphan disbursed_transaction ID {$dt->id} (points to missing student ID {$dt->stdid})\n";
    DB::table('disbursed_transaction')->where('id', $dt->id)->delete();
}
