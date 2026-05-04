<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

$deleted = DB::table('academic_program')
    ->where('name', 'Unspecified Program')
    ->orWhere('id', 2)
    ->update(['delete_status' => '1']);

echo "Success! Removed $deleted program(s).";
