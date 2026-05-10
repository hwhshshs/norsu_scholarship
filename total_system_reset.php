<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

DB::statement('SET FOREIGN_KEY_CHECKS=0;');
DB::table('billing_scholars')->truncate();
DB::table('billing_batches')->truncate();
DB::table('students')->update(['scholarship_program' => 'N/A']);
DB::statement('SET FOREIGN_KEY_CHECKS=1;');

echo "SYSTEM RESET SUCCESSFUL:\n";
echo "- All Billing Batches deleted.\n";
echo "- All Scholar entries deleted.\n";
echo "- All Student Profile locks reset to N/A.\n";
