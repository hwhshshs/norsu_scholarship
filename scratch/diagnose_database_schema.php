<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Investigating existing constraints on billing_scholars...\n";

$results = DB::select("
    SELECT 
        CONSTRAINT_NAME, 
        COLUMN_NAME, 
        REFERENCED_TABLE_NAME, 
        REFERENCED_COLUMN_NAME 
    FROM 
        INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE 
        TABLE_SCHEMA = 'schorlarship_main' 
        AND TABLE_NAME = 'billing_scholars' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
");

if (empty($results)) {
    echo "No Foreign Keys found on billing_scholars.\n";
} else {
    foreach ($results as $res) {
        echo "- {$res->CONSTRAINT_NAME}: {$res->COLUMN_NAME} -> {$res->REFERENCED_TABLE_NAME}.{$res->REFERENCED_COLUMN_NAME}\n";
    }
}

echo "\nInvestigating students table...\n";
$results2 = DB::select("SHOW INDEX FROM students");
foreach ($results2 as $res) {
    echo "- Index {$res->Key_name} on {$res->Column_name} (Unique: " . ($res->Non_unique ? 'No' : 'Yes') . ")\n";
}
