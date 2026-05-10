@extends('layouts.user_type.auth')

@push('css')
<link href="{{ asset('assets/css/dashboard_premium.css') }}" rel="stylesheet" />
<style>
    .batch-card-header[aria-expanded="true"] .fa-chevron-right {
        transform: rotate(90deg);
    }
    .batch-card-header .fa-chevron-right {
        transition: transform 0.3s ease;
    }
    .stats-icon {
        background-color: #002d54 !important;
        color: #ffffff !important;
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .btn-primary-simple {
        background-color: #002d54 !important;
        color: #fff !important;
        border: none;
    }
    .btn-outline-simple {
        border: 1px solid #002d54 !important;
        color: #002d54 !important;
        background: transparent;
    }
    .text-primary-simple {
        color: #002d54 !important;
    }
    .program-header {
        background-color: #002d54 !important;
        color: #fff !important;
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.2s ease;
    }
    .program-header .program-info {
        display: flex;
        align-items: center;
    }
    .program-header i.chevron {
        transition: transform 0.3s ease;
    }
    .program-header[aria-expanded="true"] i.chevron {
        transform: rotate(180deg);
    }
    .ay-nested-header {
        background-color: #ffffff;
        border: 1px solid #e5e7eb;
        padding: 12px 15px;
        border-radius: 10px;
        margin-bottom: 10px;
        margin-left: 25px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.2s ease;
    }
    .ay-nested-header:hover {
        border-color: #002d54;
    }
    .ay-nested-header[aria-expanded="true"] i.fa-chevron-down {
        transform: rotate(180deg);
    }
    .batch-container {
        margin-left: 50px;
    }
    .progress-mini {
        height: 6px;
        border-radius: 10px;
        background-color: #e9ecef;
    }
</style>
@endpush

@section('content')

<div class="row">
    <div class="col-12">
        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                <div class="card glass-card">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-capitalize font-weight-bold">Total Amount</p>
                                    <h5 class="font-weight-bolder mb-0 text-primary-simple">
                                        ₱{{ number_format($totals['amount'], 2) }}
                                    </h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape stats-icon text-center border-radius-md">
                                    <i class="fas fa-coins text-lg opacity-10" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                <div class="card glass-card">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-capitalize font-weight-bold">Total Scholars</p>
                                    <h5 class="font-weight-bolder mb-0">
                                        {{ number_format($totals['scholars']) }}
                                    </h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape stats-icon text-center border-radius-md">
                                    <i class="fas fa-user-graduate text-lg opacity-10" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="card glass-card">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-capitalize font-weight-bold">Total Batches</p>
                                    <h5 class="font-weight-bolder mb-0">
                                        {{ $totals['count'] }}
                                    </h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape stats-icon text-center border-radius-md">
                                    <i class="fas fa-layer-group text-lg opacity-10" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="font-weight-bolder mb-0">Disbursement Management</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('disbursement.export-all-csv') }}" class="btn btn-sm btn-outline-success mb-0 shadow-sm border-2">
                    <i class="fas fa-file-excel me-2"></i> Masterlist (Excel)
                </a>
                <a href="{{ route('disbursement.master-summary') }}" target="_blank" class="btn btn-sm btn-outline-danger mb-0 shadow-sm border-2">
                    <i class="fas fa-file-pdf me-2"></i> Summary Audit (PDF)
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4 glass-card border-0 shadow-none">
            <div class="card-body p-3">
                <form action="{{ route('disbursement.index') }}" method="GET" class="row align-items-center">
                    <div class="col-md-5">
                        <label class="text-xs font-weight-bold text-uppercase opacity-7">Semester</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            <input type="text" name="semester" class="form-control form-control-sm" value="{{ request('semester') }}" placeholder="1st Semester">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="text-xs font-weight-bold text-uppercase opacity-7">Academic Year</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-history"></i></span>
                            <input type="text" name="ay" class="form-control form-control-sm" value="{{ request('ay') }}" placeholder="2024-2025">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end pt-4">
                        <button type="submit" class="btn btn-primary-simple btn-icon-only mb-0 me-2" title="Apply Filters">
                            <i class="fas fa-filter"></i>
                        </button>
                        @if(request()->anyFilled(['semester', 'ay', 'program']))
                            <a href="{{ route('disbursement.index') }}" class="btn btn-outline-simple btn-icon-only mb-0" title="Clear Search">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Folder-Style Accordion -->
        @foreach($batches->groupBy('program') as $program => $programBatches)
            @php $progId = 'prog_' . preg_replace('/[^a-zA-Z0-9]/', '', $program); @endphp
            
            <!-- Level 1: Program Header -->
            <div class="program-header" 
                 data-bs-toggle="collapse" 
                 data-bs-target="#{{ $progId }}" 
                 aria-expanded="{{ request('program') == $program ? 'true' : 'false' }}">
                <div class="program-info">
                    <div class="icon icon-sm bg-white text-primary-simple border-radius-sm me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <h6 class="mb-0 text-white font-weight-bolder text-uppercase">{{ $program }}</h6>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-white text-primary-simple me-3">{{ $programBatches->count() }} Batches</span>
                    <i class="fas fa-chevron-down chevron text-xs text-white"></i>
                </div>
            </div>

            <div class="collapse {{ (request('program') == $program || $batches->groupBy('program')->count() == 1) ? 'show' : '' }}" id="{{ $progId }}">
                @foreach($programBatches->groupBy('ay') as $ay => $ayBatches)
                    @php $ayId = $progId . '_ay_' . str_replace('-', '', $ay); @endphp
                    
                    <!-- Level 2: Academic Year Header -->
                    <div class="ay-nested-header" 
                         data-bs-toggle="collapse" 
                         data-bs-target="#{{ $ayId }}" 
                         aria-expanded="false">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-calendar-alt text-primary-simple me-2"></i>
                            <span class="text-sm font-weight-bold text-dark">Academic Year {{ $ay }}</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="text-xs text-secondary me-3">{{ $ayBatches->count() }} Records</span>
                            <i class="fas fa-chevron-down text-xs text-secondary"></i>
                        </div>
                    </div>

                    <div class="collapse" id="{{ $ayId }}">
                        <div class="batch-container">
                            @foreach($ayBatches as $batch)
                                <!-- Level 3: FULL Detailed Disbursement Card -->
                                <div class="card mb-3 batch-card" style="border-left: 4px solid #002d54;">
                                    <div class="card-header p-3 bg-white batch-card-header" 
                                         id="heading{{ $batch->id }}" 
                                         data-bs-toggle="collapse" 
                                         data-bs-target="#collapse{{ $batch->id }}" 
                                         aria-expanded="false" 
                                         style="cursor: pointer; border-bottom: 0;">
                                        <div class="row align-items-center">
                                            <!-- Left Cluster -->
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <span class="badge bg-primary-simple px-3 mb-1">{{ $batch->program }}</span>
                                                        <h6 class="mb-0 text-sm font-weight-bold">{{ $batch->batch ?? 'Batch Unnamed' }}</h6>
                                                        <p class="text-xs text-secondary mb-0">
                                                            {{ $batch->semester }} | ADA No: <span class="text-dark font-weight-bold">{{ $batch->ada_no ?: 'Pending' }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Right Cluster -->
                                            <div class="col-md-6 text-end">
                                                <div class="d-flex flex-column align-items-end">
                                                    <div class="h5 font-weight-bolder text-primary-simple mb-1">₱{{ number_format($batch->amount, 2) }}</div>
                                                    <div style="width: 220px;">
                                                        @php 
                                                            $percent = $batch->scholar_count > 0 ? ($batch->disbursed_count / $batch->scholar_count) * 100 : 0;
                                                        @endphp
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <span class="text-xxs font-weight-bold text-secondary">Paid: {{ $batch->disbursed_count }}/{{ $batch->scholar_count }}</span>
                                                            <span class="text-xxs font-weight-bold {{ $percent == 100 ? 'text-success' : 'text-primary-simple' }}">{{ round($percent) }}%</span>
                                                        </div>
                                                        <div class="progress progress-mini mb-0">
                                                            <div class="progress-bar bg-primary-simple" role="progressbar" style="width: {{ $percent }}%"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Collapsible Body (EXACT Field List) -->
                                    <div id="collapse{{ $batch->id }}" class="collapse" aria-labelledby="heading{{ $batch->id }}">
                                        <div class="card-body p-0 border-top bg-gray-100">
                                            <div class="table-responsive">
                                                <table class="table table-bordered mb-0 bg-white">
                                                    <tbody>
                                                        <tr>
                                                            <th class="w-40 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3 py-2">Program</th>
                                                            <td class="text-xs font-weight-bold px-3 py-2">{{ $batch->program }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th class="w-40 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3 py-2">Semester</th>
                                                            <td class="text-xs font-weight-bold px-3 py-2">{{ $batch->semester }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th class="w-40 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3 py-2">Batch</th>
                                                            <td class="text-xs font-weight-bold px-3 py-2">{{ $batch->batch ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th class="w-40 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3 py-2">AY</th>
                                                            <td class="text-xs font-weight-bold px-3 py-2">{{ $batch->ay }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th class="w-40 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3 py-2">Sem</th>
                                                            <td class="text-xs font-weight-bold px-3 py-2">{{ $batch->semester }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th class="w-40 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3 py-2">Region</th>
                                                            <td class="text-xs font-weight-bold px-3 py-2">{{ $batch->region ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th class="w-40 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3 py-2">No Scholars (View the number of Scholars)</th>
                                                            <td class="text-xs font-weight-bold px-3 py-2">
                                                                <a href="{{ route('disbursement.show', $batch->id) }}" class="text-primary-simple text-decoration-underline">
                                                                    {{ $batch->scholar_count }} Scholars
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th class="w-40 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3 py-2">Date of Billing</th>
                                                            <td class="text-xs font-weight-bold px-3 py-2">{{ $batch->billing_date ? \Carbon\Carbon::parse($batch->billing_date)->format('M d, Y') : 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th class="w-40 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3 py-2">Amount</th>
                                                            <td class="text-xs font-weight-bold text-primary-simple px-3 py-2">₱{{ number_format($batch->amount, 2) }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th class="w-40 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3 py-2">Date on ADA Details</th>
                                                            <td class="text-xs font-weight-bold px-3 py-2">{{ $batch->ada_date ? \Carbon\Carbon::parse($batch->ada_date)->format('M d, Y') : 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th class="w-40 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3 py-2">ADA No.</th>
                                                            <td class="text-xs font-weight-bold px-3 py-2">{{ $batch->ada_no ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th class="w-40 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3 py-2">OR NUMBER</th>
                                                            <td class="text-xs font-weight-bold px-3 py-2">{{ $batch->or_number ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th class="w-40 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3 py-2">OR DATE</th>
                                                            <td class="text-xs font-weight-bold px-3 py-2">{{ $batch->or_date ?: 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th class="w-40 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3 py-2">STATUS (NO OF STUDENTS DISBURSE)</th>
                                                            <td class="text-xs font-weight-bold px-3 py-2">
                                                                <span class="text-primary-simple">{{ $batch->disbursed_count }}</span> / {{ $batch->scholar_count }}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th class="w-40 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3 py-2">Disbursement Attachment</th>
                                                            <td class="text-xs font-weight-bold px-3 py-2">
                                                                @if($batch->disbursement_attachment)
                                                                    <a href="{{ asset('storage/' . $batch->disbursement_attachment) }}" target="_blank" class="text-success text-decoration-underline">
                                                                        <i class="fas fa-file-pdf me-1"></i> View Disbursement PDF
                                                                    </a>
                                                                @else
                                                                    <span class="text-secondary">None</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th class="w-40 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3 py-2">Disbursement Attachment</th>
                                                            <td class="text-xs font-weight-bold px-3 py-2">
                                                                @if($batch->disbursement_scholar_file)
                                                                    <a href="{{ asset('storage/' . $batch->disbursement_scholar_file) }}" target="_blank" class="text-info font-weight-bold">
                                                                        <i class="fas fa-ellipsis-v me-1"></i><i class="fas fa-chevron-right me-1"></i> View Attachment
                                                                    </a>
                                                                @else
                                                                    <span class="text-secondary">None</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="p-3 bg-white d-flex justify-content-between align-items-center border-top">
                                                <div>
                                                    <a href="{{ route('disbursement.export-csv', $batch->id) }}" class="btn btn-sm btn-outline-success mb-0 me-2 shadow-none">
                                                        <i class="fas fa-file-excel me-1"></i> Excel
                                                    </a>
                                                    <a href="{{ route('disbursement.print-report', $batch->id) }}" target="_blank" class="btn btn-sm btn-outline-danger mb-0 shadow-none">
                                                        <i class="fas fa-file-pdf me-1"></i> Print PDF
                                                    </a>
                                                </div>
                                                <a href="{{ route('disbursement.show', $batch->id) }}" class="btn btn-primary-simple btn-sm mb-0">
                                                    <i class="fas fa-ellipsis-v me-1"></i><i class="fas fa-chevron-right me-2"></i>Manage Disbursement List
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>

@endsection
