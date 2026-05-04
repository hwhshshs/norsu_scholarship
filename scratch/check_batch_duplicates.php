<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

$duplicates = DB::table('billing_batch')
    ->select('ada_no', 'or_no', DB::raw('COUNT(*) as count'))
    ->whereNotNull('ada_no')
    ->where('ada_no', '<>', '')
    ->groupBy('ada_no', 'or_no')
    ->having('count', '>', 1)
    ->get();

if ($duplicates->count() > 0) {
    echo "Found duplicate batches:\n";
    foreach ($duplicates as $d) {
        echo "ADA: {$d->ada_no} | OR: {$d->or_no} | Count: {$d->count}\n";
        
        $batches = DB::table('billing_batch')
            ->where('ada_no', $d->ada_no)
            ->where('or_no', $d->or_no)
            ->get();
            
        foreach($batches as $b) {
            echo "   -> Batch ID: {$b->id} | Program: {$b->program} | Sem: {$b->semester} | Created: {$b->created_at}\n";
        }
    }
} else {
    echo "No duplicate batches found by ADA/OR.\n";
}
