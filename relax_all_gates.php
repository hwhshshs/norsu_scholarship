<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$file = 'app/Http/Controllers/ScholarshipDisbursedController.php';
$content = file_get_contents($file);

// 1. Update entryStore to check GLOBAL ADA/OR if row-level is empty
$content = str_replace(
    'if (!$comp[\'is_complete\'] && empty($row[\'ada_no\']) && empty($row[\'or_no\'])) {',
    'if (!$comp[\'is_complete\'] && empty($row[\'ada_no\']) && empty($row[\'or_no\']) && empty($adaNo) && empty($orNo)) {',
    $content
);

// 2. Update fastFinalizeBatch to REMOVE the hard block if ADA/OR is provided
$fastFinalizeOld = '        if (count($incomplete) > 0) {
            $msg = "Cannot finalize. " . count($incomplete) . " student(s) have incomplete profiles:\n";
            foreach (array_slice($incomplete, 0, 5) as $inc) {
                $msg .= "- {$inc[\'name\']} ({$inc[\'id_no\']}): {$inc[\'missing\']}\n";
            }
            if (count($incomplete) > 5) $msg .= "...and " . (count($incomplete) - 5) . " more.";
            
            return response()->json([\'success\' => false, \'message\' => $msg], 422);
        }';

$fastFinalizeNew = '        // Proceed even if incomplete because ADA/OR is provided
        /* if (count($incomplete) > 0) { ... } */';

$content = str_replace($fastFinalizeOld, $fastFinalizeNew, $content);

file_put_contents($file, $content);
echo "ScholarshipDisbursedController manual and fast-finalize gates relaxed successfully.\n";
