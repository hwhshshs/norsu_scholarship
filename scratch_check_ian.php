<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Support\ScholarshipMonitoring;

$name = 'Ian Christian Maranga';
$student = DB::table('student')->where('sname', 'like', "%$name%")->first();

if (!$student) {
    echo "Student not found.\n";
    exit;
}

echo "Student: " . $student->sname . " (ID: " . $student->id . ")\n";
echo "Profile Details:\n";
print_r($student);

$comp = ScholarshipMonitoring::isProfileComplete($student);
echo "\nProfile Completeness: " . ($comp['is_complete'] ? "COMPLETE" : "INCOMPLETE") . "\n";
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
