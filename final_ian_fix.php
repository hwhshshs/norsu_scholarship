<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Fix Ian (ID 42) one last time with the correct field split
DB::table('student')->where('id', 42)->update([
    'given_name' => 'Ian Christian',
    'last_name' => 'Maranga',
    'middle_initial' => '',
    'sname' => 'Maranga, Ian Christian'
]);
echo "Final manual fix for Ian (ID 42) completed.\n";
