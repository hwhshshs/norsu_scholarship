<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- GHOST INVESTIGATION ---\n";

// 1. Get all batches for March 11, 2021
$batches = DB::table('billing_batch')
    ->where('delete_status', '0')
    ->get();

foreach ($batches as $b) {
    echo "Batch ID: {$b->id}, Program: {$b->program}, Date: {$b->billing_date}\n";
    
    $txs = DB::table('fees_transaction as ft')
        ->leftJoin('student as s', 's.id', '=', 'ft.stdid')
        ->where('ft.billing_batch_id', $b->id)
        ->select('ft.id as tid', 'ft.stdid', 's.given_name', 's.last_name', 's.student_id_no')
        ->get();
    
    if ($txs->isEmpty()) {
        echo "  (No transactions found)\n";
    }

    foreach ($txs as $t) {
        $name = trim(($t->given_name ?? '') . ' ' . ($t->last_name ?? ''));
        if ($name === '') $name = '[EMPTY NAME]';
        echo "  - Transaction ID: {$t->tid}, Student ID: {$t->stdid}, ID No: {$t->student_id_no}, Name: $name\n";
    }
}

echo "--- INVESTIGATION COMPLETE ---\n";
