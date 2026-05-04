@extends('layouts.user_type.auth')

@section('content')

@php
  $summaryData = is_array($summary ?? null) ? $summary : [];
@endphp

<style>
  .badge-status {
    width: 120px;
    font-size: 0.65rem !important;
    padding: 0.4rem 0.5rem !important;
    display: inline-block;
    text-align: center;
    border-radius: 6px !important;
  }
  .btn-action-report {
    background-color: #fff;
    color: #003366;
    border: 1px solid #e9ecef;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    margin-bottom: 0;
  }
  .btn-action-report:hover {
    background-color: #fff;
    color: #2152ff !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.07), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    transform: translateY(-2px);
    border-color: rgba(33, 82, 255, 0.2);
  }
  .table tbody tr td {
    padding: 12px 16px !important;
    vertical-align: middle;
  }
  .table tbody tr {
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
  }
  .table tbody tr:hover {
    border-left: 3px solid #2152ff !important;
    background-color: rgba(33, 82, 255, 0.02) !important;
  }
  .info-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    font-weight: 700;
    color: #8392ab;
    margin-bottom: 2px;
    display: block;
  }
</style>

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body d-md-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-1">Student Report (Native)</h5>
          <p class="text-sm mb-0">Reporting view for student counts, billing balances, and status filters.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex flex-wrap">
          <a href="{{ route('scholarship-students.index') }}" class="btn btn-outline-dark mb-0 me-2">Student Management</a>
          <a href="{{ route('scholarship-system.module', 'student-report') }}" class="btn btn-outline-primary mb-0">Legacy Page</a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-3 col-6">
    <div class="card">
      <div class="card-body">
        <p class="text-sm mb-1">Rows</p>
        <h6 class="mb-0">{{ number_format((int) ($summaryData['rows'] ?? 0)) }}</h6>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card">
      <div class="card-body">
        <p class="text-sm mb-1">Active</p>
        <h6 class="mb-0 text-success">{{ number_format((int) ($summaryData['active_rows'] ?? 0)) }}</h6>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-6 mt-3 mt-md-0">
    <div class="card">
      <div class="card-body">
        <p class="text-sm mb-1">Inactive</p>
        <h6 class="mb-0 text-danger">{{ number_format((int) ($summaryData['inactive_rows'] ?? 0)) }}</h6>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-6 mt-3 mt-md-0">
    <div class="card">
      <div class="card-body">
        <p class="text-sm mb-1">Net Outstanding</p>
        <h6 class="mb-0">{{ number_format((float) ($summaryData['total_balance'] ?? 0), 2) }}</h6>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header pb-0">
        <h6 class="mb-0">Filters</h6>
      </div>
      <div class="card-body pt-3">
        <form method="GET" action="{{ route('scholarship-students.report') }}" class="row g-3 align-items-end">
          <div class="col-xl-2 col-lg-4 col-md-6">
            <label class="form-label">Search</label>
            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="ID, name, contact, email" />
          </div>

          <div class="col-xl-2 col-lg-2 col-md-6">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
              <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
              <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
          </div>

          <div class="col-xl-2 col-lg-2 col-md-6">
            <label class="form-label">Program</label>
            <select name="program" class="form-control">
              <option value="">Any</option>
              @foreach ($programOptions as $option)
                <option value="{{ $option }}" {{ $program === $option ? 'selected' : '' }}>{{ $option }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-xl-2 col-lg-2 col-md-6">
            <label class="form-label">Academic Year</label>
            <select name="academic_year" class="form-control">
              <option value="">Any</option>
              @foreach ($academicYearOptions as $option)
                <option value="{{ $option }}" {{ $academicYear === $option ? 'selected' : '' }}>{{ $option }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-xl-2 col-lg-2 col-md-6">
            <label class="form-label">Semester</label>
            <select name="semester" class="form-control">
              <option value="">Any</option>
              @foreach ($semesterOptions as $option)
                <option value="{{ $option }}" {{ $semester === $option ? 'selected' : '' }}>{{ $option }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-xl-2 col-lg-4 col-md-12">
            <div class="d-flex flex-wrap justify-content-xl-end justify-content-lg-start" style="gap: 0.5rem;">
              <button type="submit" class="btn btn-sm bg-gradient-dark mb-0" style="min-width: 84px;">Apply</button>
              <a href="{{ route('scholarship-students.report') }}" class="btn btn-sm btn-outline-secondary mb-0" style="min-width: 84px;">Reset</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-12">
    <div class="alert alert-info text-white mb-0" role="alert">
      Total Fees: <strong>{{ number_format((float) ($summaryData['total_fees'] ?? 0), 2) }}</strong> |
      Total Balance: <strong>{{ number_format((float) ($summaryData['total_balance'] ?? 0), 2) }}</strong>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header pb-0">
        <h6 class="mb-0">Student Rows</h6>
      </div>
      <div class="card-body px-0 pt-2 pb-0">
        <div class="table-responsive p-0">
          <table class="table align-items-center mb-0">
            <thead>
              <tr>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">Student</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Program / Year</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Term</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Contact</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Fees</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Balance</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Status</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-end pe-3">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($students as $student)
                @php
                  $isInactive = (string) ($student->delete_status ?? '0') === '1';
                  $financialStatus = trim((string) ($student->financial_status ?? 'not_billed'));
                  $displayProgram = trim((string) ($student->scholarship_program ?? '')) !== ''
                    ? (string) $student->scholarship_program
                    : (string) $student->degree_program;
                  if (trim($displayProgram) === '') {
                    $displayProgram = '-';
                  }
                  $displayName = trim((string) ($student->sname ?? ''));
                  if ($displayName === '') {
                    $displayName = trim((string) ($student->last_name ?? ''));
                    if (trim((string) ($student->given_name ?? '')) !== '') {
                      $displayName .= ($displayName !== '' ? ', ' : '') . trim((string) ($student->given_name ?? ''));
                    }
                  }
                @endphp
                <tr>
                  <td class="ps-3">
                    <p class="text-sm font-weight-bold mb-0 text-dark">{{ $displayName !== '' ? $displayName : ('Student #' . ($student->id ?? '')) }}</p>
                    <p class="text-xs text-secondary mb-0">{{ $student->student_id_no ?: '-' }} | {{ $student->emailid ?: '-' }}</p>
                  </td>
                  <td class="text-sm">
                    <div class="d-flex flex-column">
                      <span class="font-weight-bold text-dark">{{ $displayProgram }}</span>
                      <span class="text-xs text-secondary">{{ $student->year_level ?: ($student->grade ?: '-') }}</span>
                    </div>
                  </td>
                  <td class="text-sm">
                    <div class="d-flex align-items-center">
                      <i class="fas fa-calendar-alt text-xs text-secondary me-2"></i>
                      <span>{{ $student->scholarship_academic_year ?: '-' }}</span>
                    </div>
                    <div class="text-xxs text-secondary ps-4">{{ $student->scholarship_semester ?: '-' }}</div>
                  </td>
                  <td class="text-sm">
                    <div class="d-flex align-items-center">
                      <i class="fas fa-phone-alt text-xs text-secondary me-2"></i>
                      <span>{{ $student->contact ?: '-' }}</span>
                    </div>
                  </td>
                  <td class="text-sm text-center">
                    <span class="font-weight-bold text-dark">{{ number_format((float) ($student->fees ?? 0), 2) }}</span>
                  </td>
                  <td class="text-sm text-center">
                    <span class="font-weight-bold text-primary">{{ number_format((float) ($student->balance ?? 0), 2) }}</span>
                  </td>
                  <td class="text-center">
                    <div class="d-flex flex-column align-items-center gap-1">
                      <span class="badge badge-status {{ $isInactive ? 'bg-gradient-danger' : 'bg-gradient-success' }}">{{ $isInactive ? 'INACTIVE' : 'ACTIVE' }}</span>
                      @if ($financialStatus === 'conflict')
                        <span class="badge badge-status bg-gradient-danger">Conflict</span>
                      @elseif ($financialStatus === 'disbursed')
                        <span class="badge badge-status bg-gradient-info">Billed &amp; Disbursed</span>
                      @elseif ($financialStatus === 'billed')
                        <span class="badge badge-status bg-gradient-warning">Billed</span>
                      @else
                        <span class="badge badge-status bg-gradient-secondary">Not Billed</span>
                      @endif
                    </div>
                  </td>
                  <td class="text-end pe-3">
                    <a href="{{ route('scholarship-students.show', $student->id) }}" class="btn-action-report" data-bs-toggle="tooltip" title="View Profile">
                      <i class="fas fa-eye"></i>
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center text-sm text-secondary py-4">No student rows found for selected filters.</td>
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

@push('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Tooltips initialization
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
  });
</script>
@endpush
