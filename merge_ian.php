<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// 1. Point all transactions to ID 40 (the full name record)
DB::table('fees_transaction')->where('stdid', 42)->update(['stdid' => 40]);
DB::table('disbursed_transaction')->where('stdid', 42)->update(['stdid' => 40]);
DB::table('scholar_requirement_tracker')->where('stdid', 42)->update(['stdid' => 40]);

echo "Moved all transactions from ID 42 to ID 40.\n";

// 2. Delete the duplicate ID 42
DB::table('student')->where('id', 42)->delete();
echo "Deleted duplicate record ID 42.\n";

// 3. Ensure ID 40 has the correct full name again
DB::table('student')->where('id', 40)->update([
    'given_name' => 'Ian Christian',
    'last_name' => 'Maranga',
    'sname' => 'Maranga, Ian Christian'
]);
echo "Verified full name for ID 40.\n";
