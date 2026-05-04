<?php
$file = 'c:\\Users\\markj\\scholarship_madj\\app\\Http\Controllers\\ScholarshipDisbursedController.php';
$content = file_get_contents($file);

$pattern = '/\$batchId = \(int\) \(\$batch->id \?\? 0\);/';
$replacement = "\$batchId = (int) (\$batch->id ?? 0);

        // Prevent duplicate ADA or OR numbers across batches
        \$adaNo = trim((string) (\$request->input('ada_no') ?? ''));
        \$orNo = trim((string) (\$request->input('or_no') ?? ''));

        if (\$adaNo !== '') {
            \$existingAda = DB::table('disbursed_batch_details')
                ->where('ada_no', \$adaNo)
                ->where('billing_batch_id', '<>', \$batchId)
                ->first();
            if (\$existingAda) {
                return back()->withErrors([
                    'ada_no' => 'ADA No. ' . \$adaNo . ' is already used in another batch.',
                ])->withInput();
            }
        }

        if (\$orNo !== '') {
            \$existingOr = DB::table('disbursed_batch_details')
                ->where('or_number', \$orNo)
                ->where('billing_batch_id', '<>', \$batchId)
                ->first();
            if (\$existingOr) {
                return back()->withErrors([
                    'or_no' => 'OR No. ' . \$orNo . ' is already used in another batch.',
                ])->withInput();
            }
        }";

if (preg_match($pattern, $content)) {
    $content = preg_replace($pattern, $replacement, $content);
    file_put_contents($file, $content);
    echo "Success! Applied unique validation.";
} else {
    echo "Pattern not found.";
}
