<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

$duplicates = DB::table('billing_batch')
    ->select('program', 'semester', 'billing_date', DB::raw('COUNT(*) as count'))
    ->where('delete_status', '0')
    ->groupBy('program', 'semester', 'billing_date')
    ->having('count', '>', 1)
    ->get();

if ($duplicates->count() > 0) {
    echo "Found twin batches in billing_batch:\n";
    foreach ($duplicates as $d) {
        echo "Program: {$d->program} | Sem: {$d->semester} | Date: {$d->billing_date} | Count: {$d->count}\n";
        
        $batches = DB::table('billing_batch')
            ->where('program', $d->program)
            ->where('semester', $d->semester)
            ->where('billing_date', $d->billing_date)
            ->where('delete_status', '0')
            ->get();
            
        foreach($batches as $b) {
            echo "   -> ID: {$b->id} | Created: {$b->created_at}\n";
        }
    }
} else {
    echo "No twin batches found in billing_batch.\n";
}
