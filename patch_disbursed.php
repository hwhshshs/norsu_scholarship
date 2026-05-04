<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$file = 'app/Http/Controllers/ScholarshipDisbursedController.php';
$content = file_get_contents($file);

// 1. Relax the profile gate in two places (entryStore and importProcess)
$content = str_replace(
    'if (!$comp[\'is_complete\']) {',
    'if (!$comp[\'is_complete\'] && empty($row[\'ada_no\']) && empty($row[\'or_no\'])) {',
    $content
);

// 2. Add the smart parseName function if it doesn't exist
if (strpos($content, 'function parseName') === false) {
    $smartParseName = '
    private function parseName($fullName)
    {
        $fullName = trim((string) $fullName);
        if ($fullName === \'\') {
            return [\'last_name\' => \'\', \'given_name\' => \'\', \'middle_initial\' => \'\'];
        }

        // Case 1: "Last, First Middle" format
        if (strpos($fullName, \',\') !== false) {
            $parts = array_map(\'trim\', explode(\',\', $fullName, 2));
            $lastName = $parts[0] ?? \'\';
            $rest = $parts[1] ?? \'\';
            
            $tokens = preg_split(\'/\s+/\', $rest, -1, PREG_SPLIT_NO_EMPTY);
            $tokenCount = count($tokens);
            
            if ($tokenCount > 1 && strlen($tokens[$tokenCount - 1]) === 1) {
                $middleInitial = strtoupper($tokens[$tokenCount - 1]);
                array_pop($tokens);
                $givenName = implode(\' \', $tokens);
            } else {
                $givenName = $rest;
                $middleInitial = \'\';
            }

            return [
                \'last_name\' => $lastName,
                \'given_name\' => $givenName,
                \'middle_initial\' => $middleInitial,
            ];
        }

        // Case 2: "First Middle Last" format (no comma)
        $tokens = preg_split(\'/\s+/\', $fullName, -1, PREG_SPLIT_NO_EMPTY);
        $tokenCount = count($tokens);

        if ($tokenCount === 1) {
            return [\'last_name\' => $tokens[0], \'given_name\' => \'\', \'middle_initial\' => \'\'];
        }

        if ($tokenCount === 2) {
            return [\'last_name\' => $tokens[1], \'given_name\' => $tokens[0], \'middle_initial\' => \'\'];
        }

        if (strlen($tokens[$tokenCount - 2]) === 1) {
            $lastName = $tokens[$tokenCount - 1];
            $middleInitial = strtoupper($tokens[$tokenCount - 2]);
            unset($tokens[$tokenCount - 1]);
            unset($tokens[$tokenCount - 2]);
            $givenName = implode(\' \', $tokens);
        } else {
            $lastName = $tokens[$tokenCount - 1];
            array_pop($tokens);
            $givenName = implode(\' \', $tokens);
            $middleInitial = \'\';
        }

        return [
            \'last_name\' => $lastName,
            \'given_name\' => $givenName,
            \'middle_initial\' => $middleInitial,
        ];
    }
';
    $content = preg_replace('/}\s*$/', $smartParseName . "\n}", $content);
}

file_put_contents($file, $content);
echo "ScholarshipDisbursedController patched successfully.\n";
