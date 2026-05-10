<?php
include 'vendor/autoload.php';
$app = include 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$student = DB::table('student')
    ->where('sname', 'LIKE', '%Glacita%')
    ->orWhere('last_name', 'LIKE', '%Glacita%')
    ->first();

if (!$student) {
    echo json_encode(['error' => 'Student not found']);
    exit;
}

$feesTransactions = DB::table('fees_transaction')->where('stdid', $student->id)->get();
$disbursedTransactions = DB::table('disbursed_transaction')->where('stdid', $student->id)->get();

echo json_encode([
    'student' => $student,
    'fees' => $feesTransactions,
    'disbursed' => $disbursedTransactions
], JSON_PRETTY_PRINT);
