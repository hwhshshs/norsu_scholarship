<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// 1. Find students with empty/blank names
$blankStudents = DB::table('student')
    ->where(function($q) {
        $q->whereNull('given_name')->orWhere('given_name', '')->orWhere('given_name', ' ');
    })
    ->where(function($q) {
        $q->whereNull('last_name')->orWhere('last_name', '')->orWhere('last_name', ' ');
    })
    ->get();

foreach ($blankStudents as $s) {
    echo "Found blank student ID: {$s->id}. Scrubbing transactions...\n";
    DB::table('fees_transaction')->where('stdid', $s->id)->delete();
    DB::table('disbursed_transaction')->where('stdid', $s->id)->delete();
    DB::table('student')->where('id', $s->id)->delete();
}

// 2. Also look for transactions where stdid might be a non-numeric string or invalid reference
$fees = DB::table('fees_transaction')->get();
foreach ($fees as $f) {
    $s = DB::table('student')->where('id', $f->stdid)->first();
    if (!$s || trim(($s->given_name ?? '') . ($s->last_name ?? '')) === '') {
        echo "Found orphan/blank transaction ID: {$f->id} in Batch: {$f->billing_batch_id}. Deleting...\n";
        DB::table('fees_transaction')->where('id', $f->id)->delete();
    }
}

// 3. Final empty batch cleanup
$batches = DB::table('billing_batch')->get();
foreach ($batches as $b) {
    $hasFees = DB::table('fees_transaction')->where('billing_batch_id', $b->id)->exists();
    if (!$hasFees) {
        echo "Deleting empty batch ID: {$b->id}\n";
        DB::table('billing_batch')->where('id', $b->id)->delete();
    }
}

echo "Blank student and orphan transaction scrub complete.\n";
