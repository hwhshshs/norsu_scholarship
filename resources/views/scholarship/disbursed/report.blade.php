@extends('layouts.user_type.auth')

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body d-md-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
          <a href="{{ url()->previous() }}" class="btn btn-icon-only btn-rounded btn-outline-secondary mb-0 me-3">
            <i class="fas fa-arrow-left"></i>
          </a>
          <div>
            <h5 class="mb-1">Disbursed Report (Native)</h5>
            <p class="text-sm mb-0">Disbursed report includes Scholarship Program, Semester, Disbursed Date, Disbursed Amount, ADA No, OR No., OR Date, grantee upload, and attachment upload.</p>
          </div>
        </div>
        <div class="mt-3 mt-md-0 d-flex flex-wrap gap-2">
          <a href="{{ route('scholarship-disbursed.entry.form') }}" class="btn btn-sm bg-gradient-primary mb-0 d-flex align-items-center">
            <i class="fas fa-plus me-2"></i> Disbursed Entry
          </a>
          <a href="{{ route('scholarship-monitoring.upload-history') }}" class="btn btn-sm btn-outline-dark mb-0 d-flex align-items-center">
            <i class="fas fa-history me-2"></i> Upload History
          </a>
          <a href="{{ route('scholarship-system.module', 'disbursed-report') }}" class="btn btn-sm btn-outline-primary mb-0 d-flex align-items-center">
            <i class="fas fa-external-link-alt me-2"></i> Legacy
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

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header pb-0">
        <h6 class="mb-0">Filter</h6>
      </div>
      <div class="card-body pt-3">
        <form method="GET" action="{{ route('scholarship-disbursed.report') }}" class="row g-3 align-items-end">
          <div class="col-md-6">
            <label class="form-label">Scholarship Program</label>
            <select name="program" class="form-control">
              <option value="">All</option>
              @foreach ($programOptions as $option)
                <option value="{{ $option }}" {{ $program === $option ? 'selected' : '' }}>{{ $option }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Semester</label>
            <select name="semester" class="form-control">
              <option value="">All</option>
              @foreach ($semesterOptions as $option)
                <option value="{{ $option }}" {{ $semester === $option ? 'selected' : '' }}>{{ $option }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-12 d-flex">
            <button type="submit" class="btn bg-gradient-dark mb-0 me-2">Apply</button>
            <a href="{{ route('scholarship-disbursed.report') }}" class="btn btn-outline-secondary mb-0">Reset</a>
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
        <h6 class="mb-0">Disbursed Batches</h6>
      </div>
      <div class="card-body px-0 pt-2 pb-0">
        <div class="table-responsive p-0">
          <table class="table align-items-center mb-0">
            <thead>
              <tr>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">Scholarship Program</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Semester</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Disbursed Date</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Disbursed Amount</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">ADA No</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">OR No.</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">OR Date</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Grantees: Information of Scholars (Upload)</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-3">Upload the attachments</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($rows as $row)
                @php
                  $lastDate = trim((string) ($row->last_disbursed_date ?? ''));
                  $batchAdaNo = trim((string) ($row->batch_ada_no ?? ''));
                  $batchOrNo = trim((string) ($row->batch_or_no ?? ''));
                  $orDate = trim((string) ($row->batch_or_date ?? ''));
                  $entryUrl = route('scholarship-disbursed.entry.form', [
                    'program' => $row->program,
                    'semester' => $row->semester,
                  ]);
                @endphp
                <tr>
                  <td class="ps-3">
                    <div class="d-flex align-items-center">
                      <p class="text-sm mb-0 font-weight-bold">{{ $row->program }}</p>
                      @if (isset($row->created_at) && \Illuminate\Support\Carbon::parse($row->created_at)->isToday())
                        <span class="badge badge-sm bg-gradient-info ms-2 animate__animated animate__pulse animate__infinite">
                          <i class="fas fa-bell me-1"></i> NEW
                        </span>
                      @endif
                    </div>
                  </td>
                  <td>
                    <p class="text-sm mb-0">{{ $row->semester }}</p>
                  </td>
                  <td>
                    <p class="text-sm mb-0">{{ $lastDate !== '' ? \Illuminate\Support\Carbon::parse($lastDate)->format('M d, Y') : '-' }}</p>
                  </td>
                  <td>
                    <p class="text-sm mb-0">{{ number_format((float) ($row->finalized_amount ?? 0), 2) }}</p>
                  </td>
                  <td>
                    <p class="text-sm mb-0">{{ $batchAdaNo !== '' ? $batchAdaNo : '-' }}</p>
                  </td>
                  <td>
                    <p class="text-sm mb-0">{{ $batchOrNo !== '' ? $batchOrNo : '-' }}</p>
                  </td>
                  <td>
                    <p class="text-sm mb-0">{{ $orDate !== '' && strtotime($orDate) !== false ? date('M d, Y', strtotime($orDate)) : ($orDate !== '' ? $orDate : '-') }}</p>
                  </td>
                  <td>
                    <a href="{{ $entryUrl }}" class="btn btn-link text-primary px-0 mb-0">Upload</a>
                  </td>
                  <td class="pe-3">
                    <a href="{{ $entryUrl }}" class="btn btn-link text-primary px-0 mb-0">Upload</a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="9" class="text-center text-sm text-secondary py-4">No disbursed batches found for the selected filters.</td>
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
