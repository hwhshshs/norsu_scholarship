<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScholarshipMonitoring
{
    public static function bootstrapMonitoringStructures()
    {
        // Monitoring extensions are disabled for rollback compatibility.
        return;
    }

    public static function ensureStudentMonitoringColumns()
    {
        if (!Schema::hasTable('student')) {
            return;
        }

        self::addColumnIfMissing('student', 'address', "VARCHAR(255) NOT NULL DEFAULT ''");
        self::addColumnIfMissing('student', 'birthdate', 'DATE DEFAULT NULL');
        self::addColumnIfMissing('student', 'school_name', "VARCHAR(255) NOT NULL DEFAULT ''");
        self::addColumnIfMissing('student', 'guardian_name', "VARCHAR(255) NOT NULL DEFAULT ''");
        self::addColumnIfMissing('student', 'guardian_contact', "VARCHAR(100) NOT NULL DEFAULT ''");

        self::addIndexIfMissing('student', 'idx_student_id_no_monitoring', 'ADD KEY idx_student_id_no_monitoring (student_id_no)');
    }

    public static function resolveStudentByKeys($identifier, $fullName = '', $birthdate = '', $school = '')
    {
        if (!Schema::hasTable('student')) {
            return null;
        }

        $identifier = trim((string) $identifier);
        $fullName = trim((string) $fullName);
        
        // 1. Try search by Student ID No
        if ($identifier !== '') {
            $student = DB::table('student')
                ->select(
                    'id', 'delete_status', 'given_name', 'middle_initial', 'last_name',
                    'contact', 'address', 'birthdate', 'school_name', 'guardian_name', 
                    'guardian_contact', 'degree_program', 'year_level',
                    'scholarship_program', 'scholarship_semester', 'scholarship_academic_year'
                )
                ->where('student_id_no', $identifier)
                ->where('delete_status', '0')
                ->first();

            if ($student) {
                return $student;
            }

            // 2. Try search by Internal ID (if digit)
            if (ctype_digit($identifier)) {
                $student = DB::table('student')
                    ->select(
                        'id', 'delete_status', 'given_name', 'middle_initial', 'last_name',
                        'contact', 'address', 'birthdate', 'school_name', 'guardian_name', 
                        'guardian_contact', 'degree_program', 'year_level',
                        'scholarship_program', 'scholarship_semester', 'scholarship_academic_year'
                    )
                    ->where('id', (int) $identifier)
                    ->where('delete_status', '0')
                    ->first();
                if ($student) return $student;
            }
        }

        // 3. Try search by Full Name
        if ($fullName !== '') {
            $normalizedSearch = self::normalizeCompareValue($fullName);
            
            // We search for students and compare their full names normalized
            $candidates = DB::table('student')
                ->select(
                    'id', 'delete_status', 'given_name', 'middle_initial', 'last_name',
                    'contact', 'address', 'birthdate', 'school_name', 'guardian_name', 
                    'guardian_contact', 'degree_program', 'year_level',
                    'scholarship_program', 'scholarship_semester', 'scholarship_academic_year'
                )
                ->where('delete_status', '0')
                ->get();

            foreach ($candidates as $candidate) {
                $candidateFullName = self::normalizeCompareValue(($candidate->given_name ?? '') . ' ' . ($candidate->middle_initial ?? '') . ' ' . ($candidate->last_name ?? ''));
                if ($candidateFullName === $normalizedSearch) {
                    return $candidate;
                }
                
                // Also try "Lastname, Firstname" format if the user's search string has a comma
                if (strpos($fullName, ',') !== false) {
                     $candidateReverse = self::normalizeCompareValue(($candidate->last_name ?? '') . ' ' . ($candidate->given_name ?? '') . ' ' . ($candidate->middle_initial ?? ''));
                     if ($candidateReverse === $normalizedSearch) {
                         return $candidate;
                     }
                }
            }
        }

        return null;
    }

    public static function applyStudentSmartUpdate($studentId, array $details)
    {
        $sid = (int) $studentId;
        if ($sid <= 0 || !Schema::hasTable('student')) {
            return;
        }

        $student = DB::table('student')->where('id', $sid)->first();
        if (!$student) {
            return;
        }

        $updatePayload = [];

        // Fill address if empty
        $address = trim((string) ($details['address'] ?? ''));
        if ($address !== '' && self::isEmptyValue($student->address ?? '')) {
            $updatePayload['address'] = $address;
        }

        // Fill contact if empty
        $contact = trim((string) ($details['contact'] ?? ''));
        if ($contact !== '' && self::isEmptyValue($student->contact ?? '')) {
            $updatePayload['contact'] = $contact;
        }

        // Fill names if empty or if the new name is more complete (longer)
        $givenName = trim((string) ($details['given_name'] ?? ''));
        $existingGiven = trim((string) ($student->given_name ?? ''));
        if ($givenName !== '' && (self::isEmptyValue($existingGiven) || strlen($givenName) > strlen($existingGiven))) {
            $updatePayload['given_name'] = $givenName;
        }

        $lastName = trim((string) ($details['last_name'] ?? ''));
        $existingLast = trim((string) ($student->last_name ?? ''));
        if ($lastName !== '' && (self::isEmptyValue($existingLast) || strlen($lastName) > strlen($existingLast))) {
            $updatePayload['last_name'] = $lastName;
        }

        $middleInitial = trim((string) ($details['middle_initial'] ?? ''));
        $existingMI = trim((string) ($student->middle_initial ?? ''));
        if ($middleInitial !== '' && (self::isEmptyValue($existingMI) || strlen($middleInitial) > strlen($existingMI))) {
            $updatePayload['middle_initial'] = $middleInitial;
        }
        
        // Update sname (display name) if names changed
        if (isset($updatePayload['given_name']) || isset($updatePayload['last_name']) || isset($updatePayload['middle_initial'])) {
            $gn = $updatePayload['given_name'] ?? $student->given_name ?? '';
            $ln = $updatePayload['last_name'] ?? $student->last_name ?? '';
            $mi = $updatePayload['middle_initial'] ?? $student->middle_initial ?? '';
            
            $displayName = $ln;
            if ($gn !== '') {
                $displayName .= ($displayName !== '' ? ', ' : '') . $gn;
            }
            if ($mi !== '') {
                $displayName .= ' ' . $mi;
            }
            if ($displayName !== '') {
                $updatePayload['sname'] = $displayName;
            }
        }

        // Fill birthdate if empty
        $birthdate = self::normalizeDate($details['birthdate'] ?? '');
        if ($birthdate !== '' && self::isEmptyValue($student->birthdate ?? '')) {
            $updatePayload['birthdate'] = $birthdate;
        }

        // Fill degree_program if empty
        $course = trim((string) ($details['course'] ?? ''));
        if ($course !== '' && self::isEmptyValue($student->degree_program ?? '')) {
            $updatePayload['degree_program'] = $course;
        }

        // Fill year_level if empty
        $yearLevel = trim((string) ($details['year_level'] ?? ''));
        if ($yearLevel !== '' && self::isEmptyValue($student->year_level ?? '')) {
            $updatePayload['year_level'] = $yearLevel;
            if (self::isEmptyValue($student->grade ?? '')) {
                $updatePayload['grade'] = (string) self::gradeFromYearLevel($yearLevel);
            }
        }

        // Fill school if empty
        $school = trim((string) ($details['school'] ?? ''));
        if ($school !== '' && self::isEmptyValue($student->school_name ?? '')) {
            $updatePayload['school_name'] = $school;
        }

        // Fill guardian if empty
        $guardianName = trim((string) ($details['guardian_name'] ?? ''));
        if ($guardianName !== '' && self::isEmptyValue($student->guardian_name ?? '')) {
            $updatePayload['guardian_name'] = $guardianName;
        }

        $guardianContact = trim((string) ($details['guardian_contact'] ?? ''));
        if ($guardianContact !== '' && self::isEmptyValue($student->guardian_contact ?? '')) {
            $updatePayload['guardian_contact'] = $guardianContact;
        }

        // Fill FB Link if empty
        $fbLink = trim((string) ($details['fb_link'] ?? ''));
        // Hardened FB Link update: update if current is empty/NA OR if incoming is a valid-looking URL and different
        $isNewUrl = (str_contains($fbLink, 'facebook.com') || str_contains($fbLink, 'fb.com') || str_starts_with($fbLink, 'http'));
        $currentLink = trim((string)($student->fb_link ?? ''));
        
        if ($fbLink !== '' && strtolower($fbLink) !== 'n/a' && strtolower($fbLink) !== 'none') {
            if (self::isEmptyValue($currentLink) || ($isNewUrl && $fbLink !== $currentLink)) {
                $updatePayload['fb_link'] = $fbLink;
            }
        }

        // Fill Award No if empty
        $awardNo = trim((string) ($details['award_no'] ?? ''));
        if ($awardNo !== '' && self::isEmptyValue($student->tdp_tes_award_no ?? '')) {
            $updatePayload['tdp_tes_award_no'] = $awardNo;
        }



        // Fill scholarship info if empty
        $program = trim((string) ($details['program'] ?? ''));
        if ($program !== '' && self::isEmptyValue($student->scholarship_program ?? '')) {
            $updatePayload['scholarship_program'] = $program;
        }

        $ay = trim((string) ($details['academic_year'] ?? ''));
        if ($ay !== '' && self::isEmptyValue($student->scholarship_academic_year ?? '')) {
            $updatePayload['scholarship_academic_year'] = $ay;
        }

        $sem = trim((string) ($details['semester'] ?? ''));
        if ($sem !== '' && self::isEmptyValue($student->scholarship_semester ?? '')) {
            $updatePayload['scholarship_semester'] = $sem;
        }

        if (!empty($updatePayload)) {
            try {
                \Illuminate\Support\Facades\Log::info("SmartUpdate: Updating student {$sid}", $updatePayload);
                DB::table('student')->where('id', $sid)->update($updatePayload);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("SmartUpdate Error: " . $e->getMessage());
            }
        }
    }

    public static function logUploadHistory(array $payload)
    {
        self::ensureUploadHistoryTable();

        if (!Schema::hasTable('scholar_upload_history')) {
            return;
        }

        DB::table('scholar_upload_history')->insert([
            'module_name' => $payload['module_name'] ?? 'unknown',
            'upload_type' => $payload['upload_type'] ?? 'csv',
            'file_name' => $payload['file_name'] ?? 'unknown.csv',
            'file_path' => $payload['file_path'] ?? '',
            'uploaded_by' => $payload['uploaded_by'] ?? null,
            'records_processed' => $payload['records_processed'] ?? 0,
            'successful_rows' => $payload['successful_rows'] ?? 0,
            'failed_rows' => $payload['failed_rows'] ?? 0,
            'duplicates_skipped' => $payload['duplicates_skipped'] ?? 0,
            'status' => $payload['status'] ?? 'completed',
            'summary' => $payload['summary'] ?? '',
            'created_at' => Carbon::now(),
        ]);
    }

    public static function saveUnmatchedRecord(array $payload)
    {
        self::ensureUnmatchedTable();

        if (!Schema::hasTable('scholar_unmatched_records')) {
            return;
        }

        DB::table('scholar_unmatched_records')->insert([
            'import_source' => $payload['import_source'] ?? 'unknown',
            'module_name' => $payload['module_name'] ?? '',
            'student_id_value' => $payload['student_id_value'] ?? '',
            'full_name' => $payload['full_name'] ?? '',
            'birthdate' => self::normalizeDate($payload['birthdate'] ?? ''),
            'school' => $payload['school'] ?? '',
            'billing_batch_id' => $payload['billing_batch_id'] ?? null,
            'program' => $payload['program'] ?? '',
            'academic_year' => $payload['academic_year'] ?? '',
            'semester' => $payload['semester'] ?? '',
            'batch_label' => $payload['batch_label'] ?? '',
            'region' => $payload['region'] ?? '',
            'amount' => $payload['amount'] ?? 0.00,
            'remarks' => $payload['remarks'] ?? '',
            'reason' => $payload['reason'] ?? '',
            'original_row' => $payload['original_row'] ?? null,
            'resolution_status' => $payload['resolution_status'] ?? 'pending',
            'created_at' => Carbon::now(),
        ]);
    }

    public static function saveAlert(array $payload)
    {
        self::ensureAlertsTable();

        if (!Schema::hasTable('scholar_alert_traps')) {
            return;
        }

        $key = $payload['alert_key'] ?? ('alert_' . time() . '_' . mt_rand(1000, 9999));

        DB::table('scholar_alert_traps')->updateOrInsert(
            ['alert_key' => $key],
            [
                'alert_type' => $payload['alert_type'] ?? 'general',
                'severity' => $payload['severity'] ?? 'warning',
                'stdid' => $payload['stdid'] ?? null,
                'billing_batch_id' => $payload['billing_batch_id'] ?? null,
                'message' => $payload['message'] ?? '',
                'source_module' => $payload['source_module'] ?? '',
                'is_resolved' => '0',
                'created_at' => Carbon::now(),
            ]
        );
    }

    public static function refreshAutoAlerts()
    {
        // Placeholder for automated sanity checks
        return;
    }

    public static function computeCompletionStatus(array $row)
    {
        $statuses = [
            trim((string) ($row['cor_status'] ?? 'missing')),
            trim((string) ($row['registration_form_status'] ?? 'missing')),
            trim((string) ($row['grades_status'] ?? 'missing')),
            trim((string) ($row['school_id_status'] ?? 'missing')),
            trim((string) ($row['clearance_status'] ?? 'missing')),
            trim((string) ($row['other_status'] ?? 'missing')),
        ];

        $submitted = 0;
        foreach ($statuses as $status) {
            if ($status === 'submitted') {
                $submitted++;
            }
        }

        if ($submitted <= 0) {
            return 'incomplete';
        }

        if ($submitted >= count($statuses)) {
            return 'completed';
        }

        return 'partially_complete';
    }

    private static function ensureRequirementTable()
    {
        if (!Schema::hasTable('scholar_requirement_tracker')) {
            DB::statement("CREATE TABLE IF NOT EXISTS scholar_requirement_tracker (
                id INT(11) NOT NULL AUTO_INCREMENT,
                stdid INT(11) NOT NULL,
                cor_status VARCHAR(20) NOT NULL DEFAULT 'missing',
                registration_form_status VARCHAR(20) NOT NULL DEFAULT 'missing',
                grades_status VARCHAR(20) NOT NULL DEFAULT 'missing',
                school_id_status VARCHAR(20) NOT NULL DEFAULT 'missing',
                clearance_status VARCHAR(20) NOT NULL DEFAULT 'missing',
                other_status VARCHAR(20) NOT NULL DEFAULT 'missing',
                cor_file VARCHAR(255) NOT NULL DEFAULT '',
                registration_form_file VARCHAR(255) NOT NULL DEFAULT '',
                grades_file VARCHAR(255) NOT NULL DEFAULT '',
                school_id_file VARCHAR(255) NOT NULL DEFAULT '',
                clearance_file VARCHAR(255) NOT NULL DEFAULT '',
                other_file VARCHAR(255) NOT NULL DEFAULT '',
                completion_status VARCHAR(30) NOT NULL DEFAULT 'incomplete',
                remarks VARCHAR(255) NOT NULL DEFAULT '',
                updated_by INT(11) DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_requirement_student (stdid)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            self::addColumnIfMissing('scholar_requirement_tracker', 'cor_status', "VARCHAR(20) NOT NULL DEFAULT 'missing'");
            self::addColumnIfMissing('scholar_requirement_tracker', 'registration_form_status', "VARCHAR(20) NOT NULL DEFAULT 'missing'");
            self::addColumnIfMissing('scholar_requirement_tracker', 'grades_status', "VARCHAR(20) NOT NULL DEFAULT 'missing'");
            self::addColumnIfMissing('scholar_requirement_tracker', 'school_id_status', "VARCHAR(20) NOT NULL DEFAULT 'missing'");
            self::addColumnIfMissing('scholar_requirement_tracker', 'clearance_status', "VARCHAR(20) NOT NULL DEFAULT 'missing'");
            self::addColumnIfMissing('scholar_requirement_tracker', 'other_status', "VARCHAR(20) NOT NULL DEFAULT 'missing'");
            self::addColumnIfMissing('scholar_requirement_tracker', 'cor_file', "VARCHAR(255) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_requirement_tracker', 'registration_form_file', "VARCHAR(255) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_requirement_tracker', 'grades_file', "VARCHAR(255) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_requirement_tracker', 'school_id_file', "VARCHAR(255) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_requirement_tracker', 'clearance_file', "VARCHAR(255) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_requirement_tracker', 'other_file', "VARCHAR(255) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_requirement_tracker', 'completion_status', "VARCHAR(30) NOT NULL DEFAULT 'incomplete'");
            self::addColumnIfMissing('scholar_requirement_tracker', 'remarks', "VARCHAR(255) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_requirement_tracker', 'updated_by', 'INT(11) DEFAULT NULL');
            self::addColumnIfMissing('scholar_requirement_tracker', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

            self::addIndexIfMissing('scholar_requirement_tracker', 'uk_requirement_student', 'ADD UNIQUE KEY uk_requirement_student (stdid)');
        }
    }

    private static function ensureUploadHistoryTable()
    {
        if (!Schema::hasTable('scholar_upload_history')) {
            DB::statement("CREATE TABLE IF NOT EXISTS scholar_upload_history (
                id INT(11) NOT NULL AUTO_INCREMENT,
                module_name VARCHAR(80) NOT NULL,
                upload_type VARCHAR(60) NOT NULL,
                file_name VARCHAR(255) NOT NULL,
                file_path VARCHAR(255) NOT NULL DEFAULT '',
                uploaded_by INT(11) DEFAULT NULL,
                records_processed INT(11) NOT NULL DEFAULT 0,
                successful_rows INT(11) NOT NULL DEFAULT 0,
                failed_rows INT(11) NOT NULL DEFAULT 0,
                duplicates_skipped INT(11) NOT NULL DEFAULT 0,
                status VARCHAR(30) NOT NULL DEFAULT 'completed',
                summary VARCHAR(500) NOT NULL DEFAULT '',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_upload_module_date (module_name, created_at),
                KEY idx_upload_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            self::addColumnIfMissing('scholar_upload_history', 'module_name', "VARCHAR(80) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_upload_history', 'upload_type', "VARCHAR(60) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_upload_history', 'file_name', "VARCHAR(255) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_upload_history', 'file_path', "VARCHAR(255) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_upload_history', 'uploaded_by', 'INT(11) DEFAULT NULL');
            self::addColumnIfMissing('scholar_upload_history', 'records_processed', 'INT(11) NOT NULL DEFAULT 0');
            self::addColumnIfMissing('scholar_upload_history', 'successful_rows', 'INT(11) NOT NULL DEFAULT 0');
            self::addColumnIfMissing('scholar_upload_history', 'failed_rows', 'INT(11) NOT NULL DEFAULT 0');
            self::addColumnIfMissing('scholar_upload_history', 'duplicates_skipped', 'INT(11) NOT NULL DEFAULT 0');
            self::addColumnIfMissing('scholar_upload_history', 'status', "VARCHAR(30) NOT NULL DEFAULT 'completed'");
            self::addColumnIfMissing('scholar_upload_history', 'summary', "VARCHAR(500) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_upload_history', 'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');

            self::addIndexIfMissing('scholar_upload_history', 'idx_upload_module_date', 'ADD KEY idx_upload_module_date (module_name, created_at)');
            self::addIndexIfMissing('scholar_upload_history', 'idx_upload_status', 'ADD KEY idx_upload_status (status)');
        }
    }

    private static function ensureUnmatchedTable()
    {
        if (!Schema::hasTable('scholar_unmatched_records')) {
            DB::statement("CREATE TABLE IF NOT EXISTS scholar_unmatched_records (
                id INT(11) NOT NULL AUTO_INCREMENT,
                import_source VARCHAR(60) NOT NULL,
                module_name VARCHAR(80) NOT NULL DEFAULT '',
                student_id_value VARCHAR(100) NOT NULL DEFAULT '',
                full_name VARCHAR(255) NOT NULL DEFAULT '',
                birthdate DATE DEFAULT NULL,
                school VARCHAR(255) NOT NULL DEFAULT '',
                billing_batch_id INT(11) DEFAULT NULL,
                program VARCHAR(150) NOT NULL DEFAULT '',
                academic_year VARCHAR(30) NOT NULL DEFAULT '',
                semester VARCHAR(60) NOT NULL DEFAULT '',
                batch_label VARCHAR(60) NOT NULL DEFAULT '',
                region VARCHAR(100) NOT NULL DEFAULT '',
                amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                remarks VARCHAR(255) NOT NULL DEFAULT '',
                reason VARCHAR(255) NOT NULL DEFAULT '',
                original_row TEXT,
                resolution_status VARCHAR(30) NOT NULL DEFAULT 'pending',
                linked_student_id INT(11) DEFAULT NULL,
                resolution_note VARCHAR(255) NOT NULL DEFAULT '',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_unmatched_status (resolution_status),
                KEY idx_unmatched_source (import_source)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            self::addColumnIfMissing('scholar_unmatched_records', 'module_name', "VARCHAR(80) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_unmatched_records', 'student_id_value', "VARCHAR(100) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_unmatched_records', 'full_name', "VARCHAR(255) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_unmatched_records', 'birthdate', 'DATE DEFAULT NULL');
            self::addColumnIfMissing('scholar_unmatched_records', 'school', "VARCHAR(255) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_unmatched_records', 'billing_batch_id', 'INT(11) DEFAULT NULL');
            self::addColumnIfMissing('scholar_unmatched_records', 'program', "VARCHAR(150) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_unmatched_records', 'academic_year', "VARCHAR(30) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_unmatched_records', 'semester', "VARCHAR(60) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_unmatched_records', 'batch_label', "VARCHAR(60) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_unmatched_records', 'region', "VARCHAR(100) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_unmatched_records', 'amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0.00');
            self::addColumnIfMissing('scholar_unmatched_records', 'remarks', "VARCHAR(255) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_unmatched_records', 'reason', "VARCHAR(255) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_unmatched_records', 'original_row', 'TEXT');
            self::addColumnIfMissing('scholar_unmatched_records', 'resolution_status', "VARCHAR(30) NOT NULL DEFAULT 'pending'");
            self::addColumnIfMissing('scholar_unmatched_records', 'linked_student_id', 'INT(11) DEFAULT NULL');
            self::addColumnIfMissing('scholar_unmatched_records', 'resolution_note', "VARCHAR(255) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_unmatched_records', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

            self::addIndexIfMissing('scholar_unmatched_records', 'idx_unmatched_status', 'ADD KEY idx_unmatched_status (resolution_status)');
            self::addIndexIfMissing('scholar_unmatched_records', 'idx_unmatched_source', 'ADD KEY idx_unmatched_source (import_source)');
        }
    }

    private static function ensureAlertsTable()
    {
        if (!Schema::hasTable('scholar_alert_traps')) {
            DB::statement("CREATE TABLE IF NOT EXISTS scholar_alert_traps (
                id INT(11) NOT NULL AUTO_INCREMENT,
                alert_key VARCHAR(120) NOT NULL,
                alert_type VARCHAR(80) NOT NULL,
                severity VARCHAR(20) NOT NULL DEFAULT 'warning',
                stdid INT(11) DEFAULT NULL,
                billing_batch_id INT(11) DEFAULT NULL,
                message VARCHAR(500) NOT NULL,
                source_module VARCHAR(80) NOT NULL DEFAULT '',
                is_resolved ENUM('0','1') NOT NULL DEFAULT '0',
                resolved_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_alert_key (alert_key),
                KEY idx_alert_type (alert_type),
                KEY idx_alert_resolved (is_resolved)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            self::addColumnIfMissing('scholar_alert_traps', 'alert_key', "VARCHAR(120) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_alert_traps', 'alert_type', "VARCHAR(80) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_alert_traps', 'severity', "VARCHAR(20) NOT NULL DEFAULT 'warning'");
            self::addColumnIfMissing('scholar_alert_traps', 'stdid', 'INT(11) DEFAULT NULL');
            self::addColumnIfMissing('scholar_alert_traps', 'billing_batch_id', 'INT(11) DEFAULT NULL');
            self::addColumnIfMissing('scholar_alert_traps', 'message', "VARCHAR(500) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_alert_traps', 'source_module', "VARCHAR(80) NOT NULL DEFAULT ''");
            self::addColumnIfMissing('scholar_alert_traps', 'is_resolved', "ENUM('0','1') NOT NULL DEFAULT '0'");
            self::addColumnIfMissing('scholar_alert_traps', 'resolved_at', 'DATETIME DEFAULT NULL');
            self::addColumnIfMissing('scholar_alert_traps', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

            self::addIndexIfMissing('scholar_alert_traps', 'uk_alert_key', 'ADD UNIQUE KEY uk_alert_key (alert_key)');
            self::addIndexIfMissing('scholar_alert_traps', 'idx_alert_type', 'ADD KEY idx_alert_type (alert_type)');
            self::addIndexIfMissing('scholar_alert_traps', 'idx_alert_resolved', 'ADD KEY idx_alert_resolved (is_resolved)');
        }
    }

    private static function addColumnIfMissing($table, $column, $definition)
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        if (!Schema::hasColumn($table, $column)) {
            DB::statement("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private static function addIndexIfMissing($table, $indexName, $indexSql)
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $rows = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        if (count($rows) > 0) {
            return;
        }

        try {
            DB::statement("ALTER TABLE {$table} {$indexSql}");
        } catch (\Throwable $e) {
            // Ignore index creation collisions in legacy schema.
        }
    }

    private static function normalizeDate($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return '';
        }
    }

    private static function normalizeCompareValue($value)
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/\s+/', ' ', $value);

        return trim((string) $value);
    }

    private static function isEmptyValue($value)
    {
        $v = trim((string) $value);
        return $v === '' || $v === '-' || $v === '0' || $v === '0000-00-00' || strtolower($v) === 'null' || strtolower($v) === 'n/a';
    }

    private static function gradeFromYearLevel($yearLevel)
    {
        $yearLevel = trim((string) $yearLevel);
        if ($yearLevel === '') {
            return 0;
        }

        if (preg_match('/([1-9])/', $yearLevel, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    /**
     * Check if a student's profile is administratively complete.
     */
    public static function isProfileComplete($student)
    {
        $required = [
            'contact' => 'Contact Number',
            'address' => 'Home Address',
            'birthdate' => 'Birth Date',
            'school_name' => 'School Name',
            'guardian_name' => 'Guardian Name',
            'guardian_contact' => 'Guardian Contact',
            'degree_program' => 'Degree/Course',
            'year_level' => 'Year Level',
        ];

        $missing = [];
        foreach ($required as $field => $label) {
            if (self::isEmptyValue($student->{$field} ?? '')) {
                $missing[] = $label;
            }
        }

        return [
            'is_complete' => empty($missing),
            'missing_fields' => $missing,
            'completion_percentage' => round(((count($required) - count($missing)) / count($required)) * 100),
        ];
    }
}
