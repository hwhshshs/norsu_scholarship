<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Support\ScholarshipMonitoring;

$id = 40;
$student = DB::table('student')->where('id', $id)->first();

echo "Student: " . $student->sname . " (ID: " . $student->id . ")\n";

$comp = ScholarshipMonitoring::isProfileComplete($student);
echo "Profile Completeness: " . ($comp['is_complete'] ? "COMPLETE" : "INCOMPLETE") . " (" . $comp['completion_percentage'] . "%)\n";
if (!$comp['is_complete']) {
    echo "Missing Fields: " . implode(', ', $comp['missing_fields']) . "\n";
}

$disbursed = DB::table('disbursed_transaction')
    ->where('stdid', $student->id)
    ->get();

echo "\nDisbursed Transactions (" . count($disbursed) . "):\n";
foreach ($disbursed as $d) {
    echo "- Batch: {$d->billing_batch_id}, Status: {$d->disbursed_status}, ADA: {$d->ada_no}, OR: {$d->or_no}\n";
}
