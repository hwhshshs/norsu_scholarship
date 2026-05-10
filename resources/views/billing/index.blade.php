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
    .program-tabs .nav-link {
        border-radius: 8px !important;
        transition: all 0.2s ease;
        font-weight: 500;
        color: #4b5563;
        border: 1px solid transparent;
        margin: 0 4px;
    }
    .program-tabs .nav-link.active {
        background-color: #002d54 !important;
        color: #fff !important;
        box-shadow: none !important;
    }
    .program-tabs .nav-link:hover:not(.active) {
        background-color: #f3f4f6;
        color: #002d54;
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
</style>
@endpush

@section('content')

@if(session('rejections'))
<div class="row mb-4 animate__animated animate__fadeIn">
    <div class="col-12">
        <div class="card shadow-sm border-0" style="border-top: 5px solid #002d54; border-radius: 12px;">
            <div class="card-header pb-0 bg-white border-0 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="icon icon-shape bg-primary-simple text-white shadow-sm text-center border-radius-md me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fas fa-robot text-lg"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-dark font-weight-bolder">Robot Intelligence: Import Analysis</h6>
                        <p class="text-xs text-secondary mb-0">9 Scholars were blocked to maintain total system integrity.</p>
                    </div>
                </div>
                <div class="status-badge">
                    <span class="badge badge-sm bg-outline-simple text-primary-simple border-1">
                        <i class="fas fa-check-circle me-1"></i> SECURE IMPORT
                    </span>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive border-radius-lg">
                    <table class="table align-items-center mb-0">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">Student Name</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">ID Number</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Security Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(session('rejections') as $rej)
                            <tr class="border-bottom">
                                <td class="ps-3">
                                    <h6 class="mb-0 text-sm text-dark">{{ $rej['name'] }}</h6>
                                </td>
                                <td>
                                    <p class="text-sm font-weight-bold mb-0 text-secondary">{{ $rej['id'] }}</p>
                                </td>
                                <td>
                                    <span class="badge badge-sm bg-light text-primary-simple font-weight-bold" style="border: 1px dashed #002d54;">
                                        <i class="fas fa-lock me-1"></i> {{ $rej['reason'] }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <p class="text-xxs text-secondary mb-0 italic">* This analysis is officially timestamped and saved to the master audit log.</p>
                    <a href="{{ route('admin.activity-logs') }}" class="btn btn-link text-primary-simple text-xs p-0 mb-0">
                        <i class="fas fa-history me-1"></i> View Permanent Audit Log
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row mb-4">
  <div class="col-12">
    <!-- Quick Stats Header -->
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
                                <i class="fas fa-coins text-lg" aria-hidden="true"></i>
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
                                <h5 class="font-weight-bolder mb-0 text-primary-simple">
                                    {{ number_format($totals['scholars']) }}
                                </h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape stats-icon text-center border-radius-md">
                                <i class="fas fa-user-graduate text-lg" aria-hidden="true"></i>
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
                                <h5 class="font-weight-bolder mb-0 text-primary-simple">
                                    {{ $totals['count'] }}
                                </h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape stats-icon text-center border-radius-md">
                                <i class="fas fa-layer-group text-lg" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">Billing Management</h5>
        <div class="d-flex">
            <button type="button" class="btn btn-outline-simple btn-icon-only mb-0 me-2" data-bs-toggle="modal" data-bs-target="#bulkImportModal" title="Bulk Import">
                <i class="fas fa-upload"></i>
            </button>
            <a href="{{ route('billing.create') }}" class="btn btn-primary-simple btn-icon-only mb-0" title="New Billing">
                <i class="fas fa-plus"></i>
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4 glass-card border-0 shadow-none">
        <div class="card-body p-3">
            <form action="{{ route('billing.index') }}" method="GET" class="row align-items-center">
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
                        <input type="text" name="ay" class="form-control form-control-sm" value="{{ request('ay') }}" placeholder="2025-2026">
                    </div>
                </div>
                <div class="col-md-2 d-flex mt-4">
                    <button type="submit" class="btn btn-primary-simple btn-sm btn-icon-only mb-0 me-2" title="Apply Filters">
                        <i class="fas fa-filter"></i>
                    </button>
                    @if(request()->anyFilled(['program', 'semester', 'ay']))
                        <a href="{{ route('billing.index') }}" class="btn btn-outline-simple btn-sm btn-icon-only mb-0" title="Clear Filters">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

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
                <i class="fas fa-chevron-down chevron text-xs"></i>
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

                <div class="collapse batch-container" id="{{ $ayId }}">
                    @foreach($ayBatches as $batch)
                        <!-- Level 3: Batch Card -->
                        <div class="card mb-3 batch-card" style="border-left: 4px solid #002d54;">
                            <div class="card-header p-3 bg-white batch-card-header" 
                                 id="heading{{ $batch->id }}" 
                                 data-bs-toggle="collapse" 
                                 data-bs-target="#collapse{{ $batch->id }}" 
                                 aria-expanded="false" 
                                 style="cursor: pointer; border-bottom: 0;">
                                <div class="row align-items-center">
                                    <div class="col-md-7">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <h6 class="mb-0 text-sm font-weight-bold">{{ $batch->batch ?? 'Batch Unnamed' }}</h6>
                                                <p class="text-xs text-secondary mb-0">{{ $batch->semester }} | {{ $batch->region ?? 'No Region' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5 text-end">
                                        <div class="data-cluster-right">
                                            <div class="text-sm font-weight-bolder text-primary-simple">₱{{ number_format($batch->amount, 2) }}</div>
                                            <div class="d-flex align-items-center justify-content-end mt-1">
                                                <span class="text-xs me-2">
                                                    <a href="{{ route('billing.show', $batch->id) }}" class="text-primary-simple">
                                                        <i class="fas fa-users me-1"></i> {{ $batch->scholar_count }}
                                                    </a>
                                                </span>
                                                <button type="button" 
                                                        class="btn btn-link text-primary-simple p-0 mb-0 me-3 quick-upload-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#quickScholarModal"
                                                        data-batch-id="{{ $batch->id }}"
                                                        data-batch-name="{{ $batch->batch ?? 'Unnamed Batch' }}">
                                                    <i class="fas fa-file-upload text-sm" title="Quick Upload Scholars"></i>
                                                </button>
                                                @if($batch->ada_no)
                                                    <span class="badge bg-light text-success text-xxs">Paid</span>
                                                @else
                                                    <span class="badge bg-light text-secondary text-xxs">Pending</span>
                                                @endif
                                                <i class="fas fa-chevron-right ms-3 text-xxs text-secondary opacity-50"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="collapse{{ $batch->id }}" class="collapse" aria-labelledby="heading{{ $batch->id }}">
                                <div class="card-body p-0 border-top bg-gray-100">
                                    <div class="table-responsive">
                                        <table class="table table-bordered mb-0 bg-white">
                                            <tbody>
                                                <tr>
                                                    <th class="w-25 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3">Program</th>
                                                    <td class="text-xs font-weight-bold px-3">{{ $batch->program }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="w-25 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3">Batch</th>
                                                    <td class="text-xs font-weight-bold px-3">{{ $batch->batch ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="w-25 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3">AY</th>
                                                    <td class="text-xs font-weight-bold px-3">{{ $batch->ay }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="w-25 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3">Semester</th>
                                                    <td class="text-xs font-weight-bold px-3">{{ $batch->semester }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="w-25 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3">Region</th>
                                                    <td class="text-xs font-weight-bold px-3">{{ $batch->region ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="w-25 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3">No Scholars</th>
                                                    <td class="text-xs font-weight-bold px-3">
                                                        <a href="{{ route('billing.show', $batch->id) }}" class="text-primary-simple text-decoration-underline">{{ $batch->scholar_count }} Scholars</a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="w-25 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3">Date of Billing</th>
                                                    <td class="text-xs font-weight-bold px-3">{{ $batch->billing_date ? \Carbon\Carbon::parse($batch->billing_date)->format('M d, Y') : 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="w-25 bg-gray-100 text-xxs font-weight-bolder text-uppercase text-secondary px-3">Amount</th>
                                                    <td class="text-xs font-weight-bold text-primary-simple px-3">₱{{ number_format($batch->amount, 2) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="p-2 bg-white text-end border-top d-flex justify-content-end gap-2">
                                        <div class="btn-group-icons">
                                            <a href="{{ route('billing.edit', $batch->id) }}?mode=disburse#disbursement-section" class="btn btn-icon-only btn-sm btn-outline-simple mb-0 me-1" title="Disburse Batch">
                                                <i class="fas fa-credit-card"></i>
                                            </a>
                                            <a href="{{ route('billing.edit', $batch->id) }}" class="btn btn-icon-only btn-sm btn-primary-simple mb-0 me-1" title="Edit Batch">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="{{ route('billing.show', $batch->id) }}" class="btn btn-icon-only btn-sm btn-outline-simple mb-0" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endforeach
    

    @if($batches->isEmpty())
        <div class="card glass-card">
            <div class="card-body text-center py-5">
                <div class="icon icon-shape bg-gradient-light shadow text-center border-radius-md mb-3 mx-auto">
                    <i class="ni ni-folder-17 text-lg opacity-10" aria-hidden="true"></i>
                </div>
                <p class="text-secondary font-weight-bold">No records found for the current filters.</p>
                <a href="{{ route('billing.index') }}" class="btn btn-link text-primary">Clear all filters</a>
            </div>
        </div>
    @endif

    <div class="mt-4">
        {{ $batches->links() }}
    </div>

  </div>
</div>

<!-- Bulk Import Modal -->
<div class="modal fade" id="bulkImportModal" tabindex="-1" aria-labelledby="bulkImportModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bulkImportModalLabel">Bulk Billing Import</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('billing.bulk-import') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="modal-body">
            <p class="text-sm">Upload a CSV file to create multiple batches at once. Each row represents one scholar.</p>
            <div class="alert alert-info py-2">
                <p class="text-xs text-white mb-0">
                    <i class="fas fa-info-circle me-1"></i> The system will group scholars into batches based on Program, Semester, AY, and Batch name.
                </p>
            </div>
            <div class="mb-3">
                <label for="bulk_file" class="form-label text-xs font-weight-bold">CSV File</label>
                <input class="form-control form-control-sm" type="file" id="bulk_file" name="file" accept=".csv,.txt" required>
            </div>
            <div class="text-center">
                <a href="{{ route('billing.template') }}" class="text-sm font-weight-bold text-primary">
                    <i class="fas fa-download me-1"></i> Download CSV Template
                </a>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary btn-sm">Start Import</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Quick Scholar Import Modal -->
<div class="modal fade" id="quickScholarModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Import Scholars to <span id="quickImportBatchName" class="text-primary"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="quickImportForm" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="modal-body">
            <div class="alert alert-warning py-2 mb-3">
                <p class="text-xs text-white mb-0">
                    <i class="fas fa-shield-alt me-1"></i> <strong>Duplication Guard:</strong> The system will automatically block students already receiving other scholarships for this academic year.
                </p>
            </div>
            <div class="mb-3">
                <label class="form-label text-xs font-weight-bold">CSV File (Name, ID No)</label>
                <input class="form-control form-control-sm" type="file" name="file" accept=".csv,.txt" required>
            </div>
            <div class="text-center">
                <a href="{{ route('billing.quick-template') }}" class="text-sm font-weight-bold text-primary-simple">
                    <i class="fas fa-download me-1"></i> Download CSV Template
                </a>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary-simple btn-sm">Upload & Verify</button>
          </div>
      </form>
    </div>
  </div>
</div>

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const quickUploadBtns = document.querySelectorAll('.quick-upload-btn');
        const quickImportForm = document.getElementById('quickImportForm');
        const quickImportBatchName = document.getElementById('quickImportBatchName');

        quickUploadBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const batchId = this.getAttribute('data-batch-id');
                const batchName = this.getAttribute('data-batch-name');
                
                quickImportForm.action = `/billing/${batchId}/import-scholars`;
                quickImportBatchName.textContent = batchName;
            });
        });
    });
</script>
@endpush

@endsection
