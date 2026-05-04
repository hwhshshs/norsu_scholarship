<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

echo "--- LATEST 5 BILLING BATCHES ---\n";
$batches = DB::table('billing_batch')->orderBy('id', 'desc')->limit(5)->get();
foreach ($batches as $b) {
    $count = DB::table('fees_transaction')->where('billing_batch_id', $b->id)->count();
    echo "ID: {$b->id} | Program: {$b->program} | Grantees (Column): {$b->scholar_count} | Actual Transactions: {$count}\n";
}

echo "\n--- LATEST 5 STUDENTS ---\n";
$students = DB::table('student')->orderBy('id', 'desc')->limit(5)->get();
foreach ($students as $s) {
    echo "ID: {$s->id} | No: {$s->student_id_no} | Name: {$s->sname} | Program: {$s->scholarship_program}\n";
}

echo "\n--- LATEST 5 TRANSACTIONS ---\n";
$txs = DB::table('fees_transaction')->orderBy('id', 'desc')->limit(5)->get();
foreach ($txs as $t) {
    echo "ID: {$t->id} | Student ID: {$t->stdid} | Batch ID: {$t->billing_batch_id} | Amount: {$t->paid}\n";
}
