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
            <h5 class="mb-1">Billing Report (Native)</h5>
            <p class="text-sm mb-0">Billing records focused on Scholarship Program, Semester, Billing Date, Date Submitted, Billing Amount, and documentation.</p>
          </div>
        </div>
        <div class="mt-3 mt-md-0 d-flex flex-wrap gap-2 justify-content-md-end">
          <a href="{{ route('scholarship-billing.create') }}" class="btn btn-sm bg-gradient-primary mb-0">
            <i class="fas fa-plus me-1"></i> New Billing Entry
          </a>
          <a href="{{ route('scholarship-billing.import.form') }}" class="btn btn-sm btn-outline-success mb-0">
            <i class="fas fa-file-invoice me-1"></i> Bulk Billing Import
          </a>
          <a href="{{ route('scholarship-billing.summary') }}" class="btn btn-sm btn-outline-dark mb-0">
            <i class="fas fa-chart-pie me-1"></i> Summary
          </a>
          <a href="{{ route('scholarship-monitoring.upload-history') }}" class="btn btn-sm btn-outline-dark mb-0">
            <i class="fas fa-history me-1"></i> Upload History
          </a>
          <a href="{{ route('scholarship-system.module', 'billing-report') }}" class="btn btn-sm btn-outline-secondary mb-0">
            <i class="fas fa-history me-1"></i> Legacy
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
        <form method="GET" action="{{ route('scholarship-billing.index') }}" class="row g-3 align-items-end">
          <div class="col-md-6">
            <label class="form-label">Program</label>
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
            <button type="submit" class="btn bg-gradient-dark mb-0 me-2">
              <i class="fas fa-filter me-1"></i> Apply
            </button>
            <a href="{{ route('scholarship-billing.index') }}" class="btn btn-outline-secondary mb-0">
              <i class="fas fa-undo me-1"></i> Reset
            </a>
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
        <h6 class="mb-0">Billing Batches</h6>
      </div>
      <div class="card-body px-0 pt-2 pb-0">
        <div class="table-responsive p-0">
          <table class="table align-items-center mb-0">
            <thead>
              <tr>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3"><i class="fas fa-award me-1"></i> Scholarship Program</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"><i class="fas fa-calendar-alt me-1"></i> Semester</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"><i class="fas fa-clock me-1"></i> Billing Date</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"><i class="fas fa-paper-plane me-1"></i> Date Submitted</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"><i class="fas fa-money-bill-wave me-1"></i> Billing Amount</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"><i class="fas fa-user-graduate me-1"></i> Grantees</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"><i class="fas fa-file-signature me-1"></i> Signed Doc</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($rows as $row)
                @php
                  $signedDocPath = trim((string) ($row->signed_billing_doc ?? ''));
                  $signedDocUrl = $signedDocPath !== '' ? asset($signedDocPath) : '';
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
                    <p class="text-sm mb-0">{{ $row->billing_date ? \Illuminate\Support\Carbon::parse($row->billing_date)->format('M d, Y') : '-' }}</p>
                  </td>
                  <td>
                    <p class="text-sm mb-0">{{ $row->submitted_date_to_ched ? \Illuminate\Support\Carbon::parse($row->submitted_date_to_ched)->format('M d, Y') : '-' }}</p>
                  </td>
                  <td>
                    <p class="text-sm mb-0">{{ number_format((float) ($row->billing_total_amount ?? 0), 2) }}</p>
                  </td>
                  <td>
                    <p class="text-sm mb-0">{{ number_format((int) (($row->actual_scholars ?? 0) > 0 ? $row->actual_scholars : ($row->scholar_count ?? 0))) }}</p>
                  </td>
                  <td>
                    @if ($signedDocUrl !== '')
                      <a href="{{ $signedDocUrl }}" target="_blank" rel="noopener" class="btn btn-link text-primary px-0 mb-0">
                        <i class="fas fa-eye me-1"></i> View Doc
                      </a>
                    @else
                      <p class="text-sm mb-0 text-secondary">
                        <i class="fas fa-times-circle me-1"></i> -
                      </p>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-sm text-secondary py-4">No billing batches found for the selected filters.</td>
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
