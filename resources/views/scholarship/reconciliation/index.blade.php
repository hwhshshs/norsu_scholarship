@extends('layouts.user_type.auth')

@section('content')

@php
  $filter = is_array($filters ?? null) ? $filters : [];
  $student = (string) ($filter['student'] ?? '');
  $program = (string) ($filter['program'] ?? '');
  $academicYear = (string) ($filter['academic_year'] ?? '');
  $semester = (string) ($filter['semester'] ?? '');
  $batchLabel = (string) ($filter['batch_label'] ?? '');
  $region = (string) ($filter['region'] ?? '');
  $billingBatchId = (string) ($filter['billing_batch_id'] ?? '');
  $mismatchOnly = !empty($filter['mismatch_only']);

  $summaryData = is_array($summary ?? null) ? $summary : [];
  $batchSummary = is_array($batchTotals ?? null) ? $batchTotals : [];
  $batchRows = is_array($batchProgressRows ?? null) ? $batchProgressRows : [];
  $reconRows = is_array($rows ?? null) ? $rows : [];
@endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
        <div class="d-flex align-items-center">
          <a href="{{ url()->previous() }}" class="btn btn-icon-only btn-rounded btn-outline-secondary mb-0 me-3">
            <i class="fas fa-arrow-left"></i>
          </a>
          <div>
            <h5 class="mb-1">Reconciliation Dashboard (Native)</h5>
            <p class="text-sm mb-0">Cross-check billing rows against disbursed outputs by student and batch context.</p>
          </div>
        </div>
        <div class="mt-3 mt-md-0 d-flex flex-wrap">
          <a href="{{ route('scholarship-fund-report.index') }}" class="btn btn-sm btn-outline-primary mb-0 me-2">
            <i class="fas fa-chart-bar me-1"></i> Fund Report
          </a>
          <a href="{{ route('scholarship-system.module', 'reconciliation') }}" class="btn btn-sm btn-outline-secondary mb-0">
            <i class="fas fa-history me-1"></i> Legacy Page
          </a>
        </div>
    </div>
  </div>
</div>

@if (!empty($errorMessage))
  <div class="row">
    <div class="col-12">
      <div class="alert alert-danger text-white" role="alert">{{ $errorMessage }}</div>
    </div>
  </div>
@endif

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header pb-0">
        <h6 class="mb-0">Search Filters</h6>
      </div>
      <div class="card-body pt-3">
        <form method="GET" action="{{ route('scholarship-reconciliation.index') }}" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Student</label>
            <input type="text" name="student" value="{{ $student }}" class="form-control" placeholder="Name, contact, or numeric ID" />
          </div>

          <div class="col-md-2">
            <label class="form-label">Program</label>
            <select name="program" class="form-control">
              <option value="">Any</option>
              @foreach ($programOptions as $option)
                <option value="{{ $option }}" {{ $program === $option ? 'selected' : '' }}>{{ $option }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label">Academic Year</label>
            <select name="academic_year" class="form-control">
              <option value="">Any</option>
              @foreach ($academicYearOptions as $option)
                <option value="{{ $option }}" {{ $academicYear === $option ? 'selected' : '' }}>{{ $option }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label">Semester</label>
            <input type="text" name="semester" value="{{ $semester }}" class="form-control" placeholder="Semester" />
          </div>

          <div class="col-md-2">
            <label class="form-label">Batch</label>
            <input type="text" name="batch_label" value="{{ $batchLabel }}" class="form-control" placeholder="Batch label" />
          </div>

          <div class="col-md-1">
            <label class="form-label">Region</label>
            <input type="text" name="region" value="{{ $region }}" class="form-control" placeholder="R" />
          </div>

          <div class="col-md-2">
            <label class="form-label">Billing Batch ID</label>
            <input type="text" name="billing_batch_id" value="{{ $billingBatchId }}" class="form-control" placeholder="Numeric" />
          </div>

          <div class="col-md-3">
            <div class="form-check mt-3">
              <input class="form-check-input check-icon-input" type="checkbox" id="mismatchOnly" name="mismatch_only" value="1" {{ $mismatchOnly ? 'checked' : '' }}>
              <label class="form-check-label check-icon-label" for="mismatchOnly">Show mismatch only</label>
            </div>
          </div>

          <div class="col-md-4 d-flex">
            <button type="submit" class="btn bg-gradient-dark mb-0 me-2">Apply</button>
            <a href="{{ route('scholarship-reconciliation.index') }}" class="btn btn-outline-secondary mb-0">Reset</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card bg-gradient-dark shadow-dark border-radius-xl mb-4">
      <div class="card-body p-3">
        <div class="row">
          <div class="col-8 d-flex align-items-center">
            <div class="icon icon-shape bg-gradient-info shadow text-center border-radius-md me-3">
              <i class="fas fa-microscope text-white opacity-10"></i>
            </div>
            <div>
              <h6 class="text-white mb-0">System Integrity Audit</h6>
              <p class="text-white opacity-8 text-xs mb-0">Analyzed {{ number_format(count($reconRows)) }} transaction sets. {{ $summaryData['mismatch_count'] > 0 ? $summaryData['mismatch_count'] . ' items require attention.' : 'All data perfectly aligned.' }}</p>
            </div>
          </div>
          <div class="col-4 text-end">
             @if ($summaryData['mismatch_count'] > 0)
                <span class="badge bg-gradient-danger animate__animated animate__pulse animate__infinite">ACTION REQUIRED</span>
             @else
                <span class="badge bg-gradient-success">STABLE</span>
             @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-3 col-6">
    <div class="card shadow-sm border-radius-xl"><div class="card-body p-3 text-center"><p class="text-xs text-uppercase font-weight-bold mb-1 text-secondary">Matched Rows</p><h5 class="mb-0">{{ number_format((int) ($summaryData['matched_rows'] ?? 0)) }}</h5></div></div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card shadow-sm border-radius-xl"><div class="card-body p-3 text-center"><p class="text-xs text-uppercase font-weight-bold mb-1 text-danger">Mismatches</p><h5 class="mb-0 text-danger">{{ number_format((int) ($summaryData['mismatch_count'] ?? 0)) }}</h5></div></div>
  </div>
  <div class="col-md-3 col-6 mt-3 mt-md-0">
    <div class="card shadow-sm border-radius-xl"><div class="card-body p-3 text-center"><p class="text-xs text-uppercase font-weight-bold mb-1 text-primary">Billing Total</p><h5 class="mb-0">{{ number_format((float) ($summaryData['billing_total'] ?? 0), 2) }}</h5></div></div>
  </div>
  <div class="col-md-3 col-6 mt-3 mt-md-0">
    <div class="card shadow-sm border-radius-xl"><div class="card-body p-3 text-center"><p class="text-xs text-uppercase font-weight-bold mb-1 text-success">Disbursed Total</p><h5 class="mb-0">{{ number_format((float) ($summaryData['disbursed_total'] ?? 0), 2) }}</h5></div></div>
  </div>
</div>

@if (count($batchRows) > 0)
  <div class="row mb-4">
    <div class="col-12">
      <div class="card border-radius-xl shadow-sm border-0 bg-gray-100">
        <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-around">
           <div class="text-center px-3 border-end">
              <p class="text-xxs font-weight-bold text-uppercase text-secondary mb-0">Total Batches</p>
              <h6 class="mb-0">{{ number_format((int) ($batchSummary['batches'] ?? 0)) }}</h6>
           </div>
           <div class="text-center px-3 border-end">
              <p class="text-xxs font-weight-bold text-uppercase text-secondary mb-0">Billed Scholars</p>
              <h6 class="mb-0">{{ number_format((int) ($batchSummary['billed_scholars'] ?? 0)) }}</h6>
           </div>
           <div class="text-center px-3 border-end text-success">
              <p class="text-xxs font-weight-bold text-uppercase mb-0">Finalized</p>
              <h6 class="mb-0 text-success">{{ number_format((int) ($batchSummary['finalized_scholars'] ?? 0)) }}</h6>
           </div>
           <div class="text-center px-3 border-end text-danger">
              <p class="text-xxs font-weight-bold text-uppercase mb-0">Pending</p>
              <h6 class="mb-0 text-danger">{{ number_format((int) ($batchSummary['pending_scholars'] ?? 0)) }}</h6>
           </div>
           <div class="text-center px-3 text-primary">
              <p class="text-xxs font-weight-bold text-uppercase mb-0">Variance</p>
              <h6 class="mb-0 text-primary">₱ {{ number_format((float) ($batchSummary['variance'] ?? 0), 2) }}</h6>
           </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <div class="card mb-4 shadow-sm border-radius-xl">
        <div class="card-header pb-0 p-3"><h6 class="mb-0">Batch Progression Tracking</h6></div>
        <div class="card-body px-0 pt-2 pb-0">
          <div class="table-responsive p-0">
            <table class="table align-items-center mb-0">
              <thead>
                <tr>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">Ref / Batch ID</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Context</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Progression</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Financials</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Variance</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-end pe-3">Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($batchRows as $row)
                  <tr>
                    <td class="ps-3">
                        <p class="text-xs font-weight-bold mb-0">#{{ $row['id'] }}</p>
                        <p class="text-xxs text-secondary mb-0">{{ $row['batch_label'] }}</p>
                    </td>
                    <td class="text-sm">
                        <h6 class="mb-0 text-xs font-weight-bold">{{ $row['program'] }}</h6>
                        <p class="text-xxs text-secondary mb-0">{{ $row['academic_year'] }} | {{ $row['semester'] }}</p>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="me-2 text-xs font-weight-bold">{{ round($row['progress_pct']) }}%</span>
                            <div class="progress progress-xs w-100 mb-0">
                                <div class="progress-bar {{ $row['progress_pct'] >= 100 ? 'bg-success' : 'bg-info' }}" role="progressbar" style="width: {{ $row['progress_pct'] }}%"></div>
                            </div>
                        </div>
                        <p class="text-xxs text-secondary mb-0 mt-1">{{ $row['finalized_scholars'] }} / {{ $row['expected_scholars'] }} Scholars</p>
                    </td>
                    <td>
                        <p class="text-xs font-weight-bold mb-0 text-success">₱ {{ number_format((float) $row['disbursed_total'], 2) }}</p>
                        <p class="text-xxs text-secondary mb-0">Billed: ₱ {{ number_format((float) $row['billed_total'], 2) }}</p>
                    </td>
                    <td class="text-xs font-weight-bold {{ $row['variance'] > 0 ? 'text-danger' : 'text-success' }}">
                        ₱ {{ number_format((float) $row['variance'], 2) }}
                    </td>
                    <td><span class="badge badge-sm {{ $row['status_class'] }} border-radius-sm">{{ strtoupper($row['status_label']) }}</span></td>
                    <td class="text-end pe-3">
                      <a href="{{ route('scholarship-billing.index', ['billing_batch_id' => $row['id']]) }}" class="btn btn-link text-dark text-xs px-2 mb-0" title="View Billing"><i class="fas fa-file-invoice"></i></a>
                      <a href="{{ route('scholarship-disbursed.report', ['billing_batch_id' => $row['id']]) }}" class="btn btn-link text-primary text-xs px-2 mb-0" title="View Disbursed"><i class="fas fa-hand-holding-usd"></i></a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
@endif

<div class="row">
  <div class="col-12">
    <div class="card shadow-sm border-radius-xl">
      <div class="card-header pb-0 p-3"><h6 class="mb-0">Per Student Granular Reconciliation</h6></div>
      <div class="card-body px-0 pt-2 pb-0">
        <div class="table-responsive p-0">
          <table class="table align-items-center mb-0">
            <thead>
              <tr>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">Student</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Details</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Billing</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Disbursed</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Difference</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Audit Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($reconRows as $row)
                <tr>
                  <td class="ps-3">
                    <p class="text-sm font-weight-bold mb-0">{{ ($row['sname'] !== '' ? $row['sname'] : 'Unknown') }}</p>
                    <p class="text-xxs text-secondary mb-0">ID: {{ $row['id'] }} | Batch #{{ $row['billing_batch_id'] }}</p>
                  </td>
                  <td class="text-xs">
                     <span class="text-dark font-weight-bold">{{ $row['program'] }}</span><br>
                     <span class="text-secondary">{{ $row['academic_year'] }} / {{ $row['semester'] }}</span>
                  </td>
                  <td class="text-sm font-weight-bold">₱ {{ number_format((float) $row['total_paid'], 2) }}</td>
                  <td class="text-sm font-weight-bold">₱ {{ number_format((float) $row['total_disbursed'], 2) }}</td>
                  <td class="text-sm font-weight-bold {{ abs($row['paid_vs_disbursed']) > 0.01 ? 'text-danger' : 'text-success' }}">
                    ₱ {{ number_format((float) $row['paid_vs_disbursed'], 2) }}
                  </td>
                  <td>
                    @if (empty($row['is_mismatch']))
                        <span class="badge badge-sm bg-gradient-success border-radius-sm">ALIGNED</span>
                    @else
                        @php
                           $badgeColor = 'warning';
                           if ($row['conflict_reason'] === 'Orphaned Payment' || $row['conflict_reason'] === 'Overpaid') $badgeColor = 'danger';
                        @endphp
                        <span class="badge badge-sm bg-gradient-{{ $badgeColor }} border-radius-sm" title="{{ $row['conflict_reason'] }}">
                           <i class="fas fa-exclamation-triangle me-1"></i> {{ strtoupper($row['conflict_reason']) }}
                        </span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-sm text-secondary py-5">
                    <i class="fas fa-check-circle text-success text-3xl mb-3 d-block opacity-3"></i>
                    No reconciliation rows found for selected filters.
                  </td>
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
