@extends('layouts.user_type.auth')

@section('content')

@php
  $batchData = [
    'id' => (int) data_get($batch, 'id', 0),
    'program' => (string) data_get($batch, 'program', ''),
    'semester' => (string) data_get($batch, 'semester', ''),
    'program_batch_ref' => (string) data_get($batch, 'program_batch_ref', ''),
  ];

  $detailData = [
    'date_on_ada_details' => (string) data_get($batchDetails, 'date_on_ada_details', ''),
    'ada_no' => (string) data_get($batchDetails, 'ada_no', ''),
    'or_number' => (string) data_get($batchDetails, 'or_number', ''),
    'or_date' => (string) data_get($batchDetails, 'or_date', ''),
  ];
@endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-4 shadow-sm border-radius-xl">
      <div class="card-body d-md-flex align-items-center justify-content-between p-4">
        <div class="d-flex align-items-center">
          <div class="icon icon-shape bg-gradient-info shadow-info text-center rounded-circle me-3">
            <i class="fas fa-file-invoice-dollar text-white opacity-10"></i>
          </div>
          <div>
            <h5 class="mb-1">Disbursement Record</h5>
            <p class="text-sm mb-0 text-secondary">
              <span class="font-weight-bold text-dark">{{ $batchData['program_batch_ref'] ?: 'Batch #' . $batchData['id'] }}</span> 
              &middot; {{ $batchData['program'] }} &middot; {{ $batchData['semester'] }}
            </p>
          </div>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
          <a href="{{ route('scholarship-disbursed.report') }}" class="btn btn-sm bg-gradient-dark mb-0 border-radius-md px-4">
            <i class="fas fa-arrow-left me-1"></i> Back to Report
          </a>
          <a href="{{ route('scholarship-disbursed.entry.form', ['program' => $batchData['program'], 'semester' => $batchData['semester']]) }}" class="btn btn-sm btn-outline-primary mb-0 border-radius-md">
            <i class="fas fa-plus-circle me-1"></i> Add Entry
          </a>
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
  <div class="col-md-3 col-6">
    <div class="card">
      <div class="card-body">
        <p class="text-sm mb-1">Finalized Amount</p>
        <h6 class="mb-0">{{ number_format((float) ($totals['finalized_amount'] ?? 0), 2) }}</h6>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card">
      <div class="card-body">
        <p class="text-sm mb-1">Disbursed Date</p>
        <h6 class="mb-0">{{ $detailData['date_on_ada_details'] !== '' ? $detailData['date_on_ada_details'] : '-' }}</h6>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-6 mt-3 mt-md-0">
    <div class="card">
      <div class="card-body">
        <p class="text-sm mb-1">ADA No</p>
        <h6 class="mb-0">{{ $detailData['ada_no'] !== '' ? $detailData['ada_no'] : '-' }}</h6>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-6 mt-3 mt-md-0">
    <div class="card">
      <div class="card-body">
        <p class="text-sm mb-1">OR No / OR Date</p>
        <h6 class="mb-0">{{ $detailData['or_number'] !== '' ? $detailData['or_number'] : '-' }} / {{ $detailData['or_date'] !== '' ? $detailData['or_date'] : '-' }}</h6>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header pb-0">
        <h6 class="mb-0">Disbursed Rows</h6>
      </div>
      <div class="card-body px-0 pt-2 pb-0">
        <div class="table-responsive p-0">
          <table class="table align-items-center mb-0">
            <thead>
              <tr>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">Student</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Disbursed Date</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Disbursed Amount</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">ADA No</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">OR No</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">OR Date</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Attachment</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-end pe-3">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($rows as $row)
                <tr>
                  <td class="ps-3">
                    <p class="text-sm font-weight-bold mb-0">
                      {{ $row->sname ?: ('Student #' . $row->stdid) }}
                      @if (!empty($row->fb_link) && strtolower($row->fb_link) !== 'n/a' && strtolower($row->fb_link) !== 'none')
                        <a href="{{ (str_starts_with($row->fb_link, 'http') ? $row->fb_link : 'https://' . $row->fb_link) }}" target="_blank" class="ms-1 text-info" title="View FB Profile">
                          <i class="fab fa-facebook text-info" style="font-size: 14px;"></i>
                        </a>
                      @endif
                    </p>
                    <p class="text-xs text-secondary mb-0">ID No: {{ $row->student_id_no ?: '-' }}</p>
                  </td>
                  <td><p class="text-sm mb-0">{{ $row->disbursed_date ?: '-' }}</p></td>
                  <td><p class="text-sm mb-0">{{ number_format((float) ($row->disbursed_amount ?? 0), 2) }}</p></td>
                  <td><p class="text-sm mb-0">{{ $row->ada_no ?: '-' }}</p></td>
                  <td><p class="text-sm mb-0">{{ $row->or_no ?: '-' }}</p></td>
                  <td><p class="text-sm mb-0">{{ $row->or_date ?: '-' }}</p></td>
                  <td>
                    @if (!empty($row->attachment_file))
                      <a href="{{ asset($row->attachment_file) }}" target="_blank" class="btn btn-link text-primary px-0 mb-0">Open</a>
                    @else
                      <span class="text-xs text-secondary">-</span>
                    @endif
                  </td>
                  <td class="text-end pe-3">
                    <a href="{{ route('scholarship-students.show', $row->stdid) }}" class="btn btn-link text-primary px-2 mb-0">Open</a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-sm text-secondary py-4">No disbursed rows found.</td>
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
