@extends('layouts.user_type.auth')

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card mb-4 shadow-sm border-radius-xl">
      <div class="card-body d-md-flex align-items-center justify-content-between p-4">
        <div class="d-flex align-items-center">
          <div class="icon icon-shape bg-gradient-dark shadow-dark text-center rounded-circle me-3">
            <i class="fas fa-history text-white opacity-10"></i>
          </div>
          <div>
            <h5 class="mb-1">Upload & Monitoring History</h5>
            <p class="text-sm mb-0 text-secondary">Track all spreadsheet imports and administrative data changes.</p>
          </div>
        </div>
        <div class="mt-3 mt-md-0">
          <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary mb-0 border-radius-md">
            <i class="fas fa-arrow-left me-1"></i> Back
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body pt-3">
        <form method="GET" action="{{ route('scholarship-monitoring.upload-history') }}" class="row g-3 align-items-end">
          <div class="col-md-5">
            <label class="form-label">Search Filename</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-search"></i></span>
              <input type="text" name="search" class="form-control" placeholder="Search by spreadsheet name..." value="{{ $search }}">
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Module Filter</label>
            <select name="module" class="form-control" onchange="this.form.submit()">
              <option value="">All Modules</option>
              <option value="billing_import" {{ $selectedModule === 'billing_import' ? 'selected' : '' }}>Billing Imports</option>
              <option value="disbursed_import" {{ $selectedModule === 'disbursed_import' ? 'selected' : '' }}>Disbursement Imports</option>
              <option value="student_import" {{ $selectedModule === 'student_import' ? 'selected' : '' }}>Student Imports</option>
            </select>
          </div>
          <div class="col-md-3 d-flex">
            <button type="submit" class="btn bg-gradient-dark mb-0 me-2">Apply</button>
            <a href="{{ route('scholarship-monitoring.upload-history') }}" class="btn btn-outline-secondary mb-0">Reset</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-8 col-12">
    <div class="card shadow-sm border-radius-xl">
      <div class="card-header pb-0 p-3">
        <h6 class="mb-0">Recent Activity Timeline</h6>
      </div>
      <div class="card-body p-3">
        <div class="timeline timeline-one-side" data-timeline-axis-style="dotted">
          @forelse ($history as $item)
            @php
              $statusColor = 'success';
              $icon = 'fa-file-csv';
              if (stripos($item->status, 'fail') !== false || $item->failed_rows > 0) {
                  $statusColor = 'danger';
                  $icon = 'fa-exclamation-circle';
              } elseif (stripos($item->status, 'partial') !== false) {
                  $statusColor = 'warning';
                  $icon = 'fa-file-import';
              }
              
              $moduleLabel = ucwords(str_replace(['_', 'import'], [' ', ''], $item->module_name));
              $date = \Illuminate\Support\Carbon::parse($item->created_at);
            @endphp
            <div class="timeline-block mb-4">
              <span class="timeline-step">
                <i class="fas {{ $icon }} text-{{ $statusColor }} text-gradient"></i>
              </span>
              <div class="timeline-content">
                <h6 class="text-dark text-sm font-weight-bold mb-0">
                  {{ $item->file_name }} 
                  <span class="badge badge-sm bg-light text-{{ $statusColor }} border-radius-sm ms-2" style="font-size: 0.6rem;">{{ strtoupper($item->status) }}</span>
                </h6>
                <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">
                  {{ $date->format('M d, Y h:i A') }} | <span class="text-primary text-uppercase">{{ $moduleLabel }}</span>
                </p>
                <div class="mt-2 p-3 bg-gray-100 border-radius-lg">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <p class="text-xs mb-1"><i class="fas fa-user me-1"></i> Uploaded by: <strong>{{ $item->uploader_name ?: 'System' }}</strong></p>
                      <p class="text-xs mb-0">
                        <span class="text-success font-weight-bold">{{ number_format($item->successful_rows) }} Success</span> &middot; 
                        <span class="text-danger font-weight-bold">{{ number_format($item->failed_rows) }} Failed</span> &middot; 
                        <span class="text-secondary font-weight-bold">{{ number_format($item->records_processed) }} Total</span>
                      </p>
                    </div>
                    @if ($item->file_path !== '')
                      <a href="{{ asset($item->file_path) }}" target="_blank" class="btn btn-link text-primary text-xs mb-0">
                        <i class="fas fa-download me-1"></i> Original File
                      </a>
                    @endif
                  </div>
                  @if ($item->summary !== '')
                    <div class="mt-2 border-top pt-2">
                      <p class="text-xxs text-secondary italic mb-0">{{ $item->summary }}</p>
                    </div>
                  @endif
                </div>
              </div>
            </div>
          @empty
            <div class="text-center py-5">
              <i class="fas fa-folder-open text-secondary mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
              <p class="text-sm text-secondary">No upload history found matching your filters.</p>
            </div>
          @endforelse
        </div>
        
        <div class="d-flex justify-content-center mt-4">
          {{ $history->links() }}
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-lg-4 col-12 mt-lg-0 mt-4">
    <div class="card bg-gradient-dark shadow-dark border-radius-xl">
      <div class="card-body p-4">
        <h6 class="text-white mb-3">Quick Statistics</h6>
        <div class="row">
          <div class="col-6">
            <p class="text-white opacity-8 text-xs mb-1">Total Uploads</p>
            <h5 class="text-white mb-0">{{ number_format($stats->total_count) }}</h5>
          </div>
          <div class="col-6">
            <p class="text-white opacity-8 text-xs mb-1">Global Success</p>
            @php
               $rate = ($stats->total_processed ?? 0) > 0 ? round(($stats->total_success / $stats->total_processed) * 100) : 0;
            @endphp
            <h5 class="text-white mb-0">{{ $rate }}%</h5>
          </div>
        </div>
        <hr class="horizontal light my-3">
        <div class="d-flex align-items-center">
          <i class="fas fa-info-circle text-white me-2" style="font-size: 0.8rem;"></i>
          <p class="text-white opacity-8 text-xxs mb-0">The history is preserved for auditing and data verification purposes.</p>
        </div>
      </div>
    </div>
    
    <div class="card mt-4 shadow-sm border-radius-xl border-dashed border-2">
      <div class="card-body p-4 text-center">
        <i class="fas fa-shield-alt text-primary mb-3" style="font-size: 2rem;"></i>
        <h6>Data Protection</h6>
        <p class="text-xs text-secondary">All uploads are scanned for duplicates and profile completeness before processing.</p>
        <button class="btn btn-sm btn-outline-primary mb-0 border-radius-md mt-2">Security Guidelines</button>
      </div>
    </div>
  </div>
</div>

@endsection
