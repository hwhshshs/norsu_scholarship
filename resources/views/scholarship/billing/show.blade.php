@extends('layouts.user_type.auth')

@section('content')

@php
  $batchData = is_object($batch) ? (array) $batch : (array) ($batch ?: []);
  $batchId = (int) ($batchData['id'] ?? 0);
  $programBatchRef = trim((string) ($batchData['program_batch_ref'] ?? ''));
@endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body d-md-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-1">Billing Batch Detail</h5>
          <p class="text-sm mb-0">Program Billing ID: <strong>{{ $programBatchRef !== '' ? $programBatchRef : ('#' . $batchId) }}</strong></p>
        </div>
        <div class="mt-3 mt-md-0 d-flex flex-wrap">
          <a href="{{ route('scholarship-fund-report.index') }}" class="btn btn-outline-dark mb-0 me-2">Back To Fund Report</a>
          <a href="{{ route('scholarship-system.module', 'billing-report') }}" class="btn btn-outline-primary mb-0">Legacy Page</a>
        </div>
      </div>
    </div>
  </div>
</div>

@if (session('success'))
  <div class="row">
    <div class="col-12">
      <div class="alert alert-success text-white" role="alert">{{ session('success') }}</div>
    </div>
  </div>
@endif

<div class="row mb-4">
  <div class="col-md-4 mb-3 mb-md-0">
    <div class="card h-100">
      <div class="card-body p-3">
        <p class="text-sm mb-1">Program / Term</p>
        <h6 class="mb-0">{{ $batchData['program'] ?? '' }}</h6>
        <p class="text-sm mb-0">{{ $batchData['academic_year'] ?? '' }} / {{ $batchData['semester'] ?? '' }}</p>
      </div>
    </div>
  </div>

  <div class="col-md-4 mb-3 mb-md-0">
    <div class="card h-100">
      <div class="card-body p-3">
        <p class="text-sm mb-1">Batch / Region / Dates</p>
        <h6 class="mb-0">{{ $batchData['batch_label'] ?? '' }} / {{ $batchData['region'] ?? '' }}</h6>
        <p class="text-sm mb-0">Billing: {{ !empty($batchData['billing_date']) ? \Illuminate\Support\Carbon::parse($batchData['billing_date'])->format('M d, Y') : '-' }}</p>
        <p class="text-sm mb-0">Date Submitted: {{ !empty($batchData['submitted_date_to_ched']) ? \Illuminate\Support\Carbon::parse($batchData['submitted_date_to_ched'])->format('M d, Y') : '-' }}</p>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body p-3">
        <p class="text-sm mb-1">Amounts and Counts</p>
        <h6 class="mb-0">Batch Total: {{ number_format((float) ($batchData['billing_total_amount'] ?? 0), 2) }}</h6>
        <p class="text-sm mb-0">Linked Scholars: {{ number_format($actualScholars) }} | Linked Total: {{ number_format((float) $linkedTotal, 2) }} | Conflicts: {{ number_format($conflictCount) }}</p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header pb-0">
        <h6 class="mb-0">Search Linked Students</h6>
      </div>
      <div class="card-body pt-3">
        <form method="GET" action="{{ route('scholarship-billing.show', $batchId) }}" class="row g-3 align-items-end">
          <div class="col-md-6">
            <label class="form-label">Search</label>
            <input type="text" name="student_search" class="form-control" value="{{ $studentSearch }}" placeholder="Name, student ID, contact, course, year" />
          </div>
          <div class="col-md-3">
            <div class="form-check mt-4">
              <input class="form-check-input check-icon-input" type="checkbox" id="conflicts_only" name="conflicts_only" value="1" {{ $conflictsOnly ? 'checked' : '' }}>
              <label class="form-check-label check-icon-label" for="conflicts_only">Show conflicts only</label>
            </div>
          </div>
          <div class="col-md-3 d-flex">
            <button type="submit" class="btn bg-gradient-dark mb-0 me-2">Apply</button>
            <a href="{{ route('scholarship-billing.show', $batchId) }}" class="btn btn-outline-secondary mb-0">Reset</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header pb-0">
        <h6 class="mb-0">Linked Billing Rows</h6>
      </div>
      <div class="card-body px-0 pt-2 pb-0">
        <div class="table-responsive p-0">
          <table class="table align-items-center mb-0">
            <thead>
              <tr>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">Student</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Scholarship Program</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Course / Year</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Billing Date</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Amount</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Remark</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Conflict</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-end pe-3">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($rows as $row)
                @php
                  $isConflict = (string) ($row->conflict_status ?? 'none') === 'scholarship_conflict';
                @endphp
                <tr class="{{ $isConflict ? 'table-danger' : '' }}">
                  <td class="ps-3">
                    <p class="text-sm mb-0 font-weight-bold">{{ $row->sname ?: ('Student #' . $row->stdid) }}</p>
                    <p class="text-xs mb-0 text-secondary">
                      ID: {{ $row->student_id_no ?: $row->stdid }} | Contact: {{ $row->contact ?: '-' }}
                      @if(!empty($row->fb_link) && strtolower($row->fb_link) !== 'n/a' && strtolower($row->fb_link) !== 'none')
                        | <a href="{{ (str_starts_with($row->fb_link, 'http') ? $row->fb_link : 'https://' . $row->fb_link) }}" target="_blank" class="text-primary font-weight-bold"><i class="fab fa-facebook ms-1"></i> FB Profile</a>
                      @endif
                    </p>
                  </td>
                  <td>
                    <p class="text-sm mb-0">{{ $row->scholarship_program ?: '-' }}</p>
                  </td>
                  <td>
                    <p class="text-sm mb-0">{{ $row->course ?: '-' }}</p>
                    <p class="text-xs mb-0 text-secondary">{{ $row->year_level ?: '-' }}</p>
                  </td>
                  <td>
                    <p class="text-sm mb-0">{{ $row->submitdate ? \Illuminate\Support\Carbon::parse($row->submitdate)->format('M d, Y') : '-' }}</p>
                  </td>
                  <td>
                    <p class="text-sm mb-0">{{ number_format((float) ($row->paid ?? 0), 2) }}</p>
                  </td>
                  <td>
                    <p class="text-sm mb-0">{{ $row->transcation_remark ?: '-' }}</p>
                  </td>
                  <td>
                    @php
                      $isConflict = (string) ($row->conflict_status ?? 'none') === 'scholarship_conflict';
                      $conflictNote = (string) ($row->conflict_note ?? '');
                      $isPriorYear = $isConflict && stripos($conflictNote, 'prior year') !== false;
                    @endphp
                    @if ($isConflict)
                      @if ($isPriorYear)
                        <span class="badge bg-gradient-info">Prior Year</span>
                        <p class="text-xs mb-0 mt-1 text-info">{{ $conflictNote }}</p>
                      @else
                        <span class="badge bg-gradient-danger">Conflict</span>
                        <p class="text-xs mb-0 mt-1 text-danger">{{ $conflictNote ?: 'Scholarship conflict detected.' }}</p>
                      @endif
                    @else
                      <span class="badge bg-gradient-success">None</span>
                    @endif
                  </td>
                  <td class="text-end pe-3">
                    <a href="{{ route('scholarship-students.show', $row->stdid) }}" class="btn btn-link text-primary px-2 mb-0">Open</a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-sm text-secondary py-4">No linked billing rows found for this filter.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
