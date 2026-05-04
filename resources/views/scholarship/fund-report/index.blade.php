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
            <h5 class="mb-1">Fund Report (Native)</h5>
            <p class="text-sm mb-0">Monitor total billing and disbursed amounts per program and semester.</p>
          </div>
        </div>
        <div class="mt-3 mt-md-0 d-flex flex-wrap align-items-center justify-content-md-end" style="gap: 8px;">
          <a href="{{ route('scholarship-billing.import.form') }}" class="btn btn-sm btn-outline-success mb-0">
            <i class="fas fa-file-import me-1"></i> Import Bulk Billing
          </a>
          <a href="{{ route('scholarship-disbursed.import.form') }}" class="btn btn-sm btn-outline-info mb-0">
            <i class="fas fa-file-import me-1"></i> Import Bulk Disbursed
          </a>
          <a href="{{ route('scholarship-billing.create') }}" class="btn btn-sm bg-gradient-primary mb-0 shadow-none">
            <i class="fas fa-plus me-1"></i> New Billing Entry
          </a>
          <a href="{{ route('scholarship-disbursed.entry.form') }}" class="btn btn-sm btn-outline-primary mb-0">
            <i class="fas fa-check-double me-1"></i> Disbursed Entry
          </a>
          <a href="{{ route('scholarship-monitoring.upload-history') }}" class="btn btn-sm btn-outline-dark mb-0">
            <i class="fas fa-history me-1"></i> Upload History
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header pb-0">
        <h6 class="mb-0">Filter</h6>
      </div>
      <div class="card-body pt-3">
        <form method="GET" action="{{ route('scholarship-fund-report.index') }}" class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Scholarship Program</label>
            <select name="program" class="form-control" onchange="this.form.submit()">
              <option value="">All</option>
              @foreach ($programOptions as $option)
                <option value="{{ $option }}" {{ $program === $option ? 'selected' : '' }}>{{ $option }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Semester</label>
            <select name="semester" class="form-control" onchange="this.form.submit()">
              <option value="">All</option>
              @foreach ($semesterOptions as $option)
                <option value="{{ $option }}" {{ $semester === $option ? 'selected' : '' }}>{{ $option }}</option>
              @endforeach
            </select>
          </div>


          <div class="col-md-4">
            <label class="form-label">Search Student Name or ID</label>
            <div class="input-group">
              <span class="input-group-text text-body"><i class="fas fa-search" aria-hidden="true"></i></span>
              <input type="text" name="search" id="liveSearchInput" class="form-control" placeholder="Type to search..." value="{{ $search }}" autocomplete="off">
            </div>
          </div>

          <div class="col-md-12 d-flex">
            <a href="{{ route('scholarship-fund-report.index') }}" class="btn btn-outline-secondary mb-0">Reset Filters</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header pb-0">
        <h6 class="mb-0">Billing Report</h6>
      </div>
      <div class="card-body px-0 pt-2 pb-0">
        <style>
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
          .btn-action-report i {
            font-size: 0.85rem;
          }
          .table tbody tr td {
            padding: 12px 24px !important;
          }
          .badge-grantee {
            background-color: rgba(33, 82, 255, 0.1);
            color: #2152ff;
            font-weight: 700;
            padding: 0.5em 0.85em;
            border-radius: 6px;
            font-size: 0.75rem;
          }
          .program-name {
            font-weight: 600;
            color: #344767;
            display: flex;
            align-items: center;
          }
          .program-name i {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-right: 10px;
          }
          .table tbody tr {
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
          }
          .table tbody tr:hover {
            border-left: 3px solid #2152ff !important;
            background-color: rgba(33, 82, 255, 0.03) !important;
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
        @php
          $isProgramOpened = trim((string) $program) !== '';
        @endphp
        <div class="table-responsive p-0">
          <table class="table align-items-center mb-0">
            <thead>
              <tr>
                @if ($isProgramOpened)
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3"><i class="fas fa-user-graduate me-1"></i> Grantees (Names)</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"><i class="fas fa-calendar-alt me-1"></i> Semester</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"><i class="fas fa-clock me-1"></i> Billing Date</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"><i class="fas fa-money-bill-wave me-1"></i> Billing Amount</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"><i class="fas fa-user-graduate me-1"></i> Grantees</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"><i class="fas fa-file-signature me-1"></i> Signed Doc</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-3 text-end">Actions</th>
                @else
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3"><i class="fas fa-award me-1"></i> Scholarship Program</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"><i class="fas fa-user-graduate me-1"></i> Grantees</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-3 text-end">Actions</th>
                @endif
              </tr>
            </thead>
            <tbody>
              @if ($isProgramOpened)
                @forelse ($rows as $row)
                  @php
                    $signedDocPath = trim((string) ($row->signed_billing_doc ?? ''));
                    $signedDocUrl = $signedDocPath !== '' ? asset($signedDocPath) : '';
                  @endphp
                  <tr>
                    <td class="ps-3">
                      <div class="d-flex flex-column">
                        <span class="text-xs font-weight-bold text-dark">{{ Str::limit($row->student_names ?? 'No names', 45) }}</span>
                        @if (isset($row->student_names) && strlen($row->student_names) > 45)
                          <span class="text-xxs text-secondary" data-bs-toggle="tooltip" title="{{ $row->student_names }}">...and others</span>
                        @endif
                      </div>
                    </td>
                    <td>
                      <div class="d-flex align-items-center">
                        <i class="fas fa-calendar-day text-xs text-secondary me-2"></i>
                        <span class="text-sm font-weight-bold">{{ $row->semester }}</span>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex flex-column">
                        <span class="info-label">Date</span>
                        <span class="text-xs">{{ $row->billing_date ? \Illuminate\Support\Carbon::parse($row->billing_date)->format('M d, Y') : '-' }}</span>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex flex-column">
                        <span class="info-label">Amount</span>
                        <span class="text-sm font-weight-bold text-dark">{{ number_format((float) ($row->billing_total_amount ?? 0), 2) }}</span>
                      </div>
                    </td>
                    <td>
                      <span class="badge-grantee">{{ number_format((int) (($row->actual_scholars ?? 0) > 0 ? $row->actual_scholars : ($row->scholar_count ?? 0))) }} Grantees</span>
                    </td>
                    <td>
                      @if ($signedDocUrl !== '')
                        <a href="{{ $signedDocUrl }}" target="_blank" rel="noopener" class="btn-action-report" data-bs-toggle="tooltip" title="View Document">
                          <i class="fas fa-file-pdf"></i>
                        </a>
                      @else
                        <p class="text-sm mb-0 text-secondary">-</p>
                      @endif
                    </td>
                    <td class="pe-3 text-end">
                      <a href="{{ route('scholarship-billing.show', $row->id) }}" class="btn-action-report" data-bs-toggle="tooltip" title="View Billing Details">
                        <i class="fas fa-eye"></i>
                      </a>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center text-sm text-secondary py-4">No billing batches found for the selected filters.</td>
                  </tr>
                @endforelse
              @else
                @forelse (($billingRows ?? []) as $row)
                  <tr>
                    <td class="ps-3">
                      <div class="program-name">
                        <i class="fas fa-award text-primary"></i>
                        <span class="text-sm">{{ $row->program }}</span>
                      </div>
                    </td>
                    <td>
                      <span class="badge-grantee">{{ number_format((int) ($row->actual_scholars ?? $row->scholar_count ?? 0)) }} Grantees</span>
                    </td>
                    <td class="pe-3 text-end">
                      <a href="{{ route('scholarship-fund-report.index', array_filter(['program' => $row->program, 'semester' => $semester])) }}" class="btn-action-report" data-bs-toggle="tooltip" data-bs-placement="top" title="Open Program">
                        <i class="fas fa-folder-open"></i>
                      </a>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-sm text-secondary py-4">No scholarship programs found for the selected filters.</td>
                  </tr>
                @endforelse
              @endif
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header pb-0">
        <h6 class="mb-0">Disbursed Report</h6>
      </div>
      <div class="card-body px-0 pt-2 pb-0">
        <div class="table-responsive p-0">
          <table class="table align-items-center mb-0">
            <thead>
              <tr>
                @if ($isProgramOpened)
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3"><i class="fas fa-user-graduate me-1"></i> Grantees (Names)</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"><i class="fas fa-calendar-alt me-1"></i> Semester</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"><i class="fas fa-user-graduate me-1"></i> Grantees</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"><i class="fas fa-clock me-1"></i> Disbursed Date</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"><i class="fas fa-money-bill-wave me-1"></i> Disbursed Amount</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"><i class="fas fa-info-circle me-1"></i> Status</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"><i class="fas fa-file-invoice-dollar me-1"></i> ADA / OR</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-3 text-end">Actions</th>
                @else
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3"><i class="fas fa-award me-1"></i> Scholarship Program</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"><i class="fas fa-user-graduate me-1"></i> Grantees</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-3 text-end">Actions</th>
                @endif
              </tr>
            </thead>
            <tbody>
              @if ($isProgramOpened)
                @forelse ($rows as $row)
                  @php
                    $lastDate = trim((string) ($row->last_disbursed_date ?? ''));
                    $batchAdaNo = trim((string) ($row->batch_ada_no ?? ''));
                    $batchOrNo = trim((string) ($row->batch_or_no ?? ''));
                    $orDate = trim((string) ($row->batch_or_date ?? ''));
                    $entryUrl = route('scholarship-disbursed.entry.form', [
                      'batch_id' => $row->id,
                      'program' => $row->program,
                      'semester' => $row->semester,
                    ]);
                  @endphp
                  <tr>
                    <td class="ps-3">
                      <div class="d-flex flex-column">
                        <span class="text-xs font-weight-bold text-dark">{{ Str::limit($row->student_names ?? 'No names', 45) }}</span>
                        @if (isset($row->student_names) && strlen($row->student_names) > 45)
                          <span class="text-xxs text-secondary" data-bs-toggle="tooltip" title="{{ $row->student_names }}">...and others</span>
                        @endif
                      </div>
                    </td>
                    <td>
                      <div class="d-flex align-items-center">
                        <i class="fas fa-calendar-day text-xs text-secondary me-2"></i>
                        <span class="text-sm font-weight-bold">{{ $row->semester }}</span>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex flex-column">
                        <span class="badge-grantee">{{ number_format((int) ($row->disbursed_scholars ?? 0)) }} / {{ number_format((int) ($row->actual_scholars ?? 0)) }} Finalized</span>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex flex-column">
                        <span class="info-label">Date</span>
                        <span class="text-xs">{{ $lastDate !== '' ? \Illuminate\Support\Carbon::parse($lastDate)->format('M d, Y') : '-' }}</span>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex flex-column">
                        <span class="info-label">Amount (Disbursed / Billed)</span>
                        <div class="d-flex align-items-center">
                          <span class="text-sm font-weight-bold text-dark me-2">{{ number_format((float) ($row->finalized_amount ?? 0), 2) }}</span>
                          <span class="text-xxs text-secondary">/ {{ number_format((float) ($row->billing_total_amount ?? 0), 2) }}</span>
                        </div>
                        @php
                          $percent = $row->billing_total_amount > 0 ? min(100, round(($row->finalized_amount / $row->billing_total_amount) * 100)) : 0;
                          $progressColor = $percent >= 100 ? 'bg-success' : ($percent > 0 ? 'bg-info' : 'bg-secondary');
                        @endphp
                        <div class="progress mt-1" style="height: 4px; width: 100px;">
                          <div class="progress-bar {{ $progressColor }}" role="progressbar" style="width: {{ $percent }}%" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                      </div>
                    </td>
                    <td>
                      @if ($percent >= 100)
                        <span class="badge badge-sm bg-gradient-success">Completed</span>
                      @elseif ($percent > 0)
                        <span class="badge badge-sm bg-gradient-info">Partial</span>
                      @else
                        <span class="badge badge-sm bg-gradient-secondary">Draft</span>
                      @endif
                    </td>
                    <td>
                      <div class="d-flex flex-column mb-1">
                        <span class="info-label">ADA NO</span>
                        <span class="text-xs font-weight-bold">{{ $batchAdaNo ?: '-' }}</span>
                      </div>
                      <div class="d-flex flex-column">
                        <span class="info-label">OR NO / DATE</span>
                        <span class="text-xxs text-secondary">{{ $batchOrNo ?: '-' }} ({{ $orDate !== '' && strtotime($orDate) !== false ? date('M d, Y', strtotime($orDate)) : ($orDate !== '' ? $orDate : '-') }})</span>
                      </div>
                    </td>
                    <td class="pe-3 text-end">
                      <a href="{{ route('scholarship-disbursed.show', $row->id) }}" class="btn-action-report me-2" data-bs-toggle="tooltip" title="View Disbursed Details">
                        <i class="fas fa-eye"></i>
                      </a>
                      <a href="javascript:void(0)" class="btn-action-report me-2" onclick="openFastFinalize('{{ $row->id }}', '{{ $row->program }}', '{{ $row->semester }}', '{{ $row->billing_total_amount }}')" data-bs-toggle="tooltip" title="Fast Finalize Entire Batch">
                        <i class="fas fa-bolt text-warning"></i>
                      </a>
                      <a href="{{ $entryUrl }}" class="btn-action-report" data-bs-toggle="tooltip" title="Manual Entry">
                        <i class="fas fa-check-circle"></i>
                      </a>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="text-center text-sm text-secondary py-4">No disbursed batches found for the selected filters.</td>
                  </tr>
                @endforelse
              @else
                @php
                  $disbursedProgramRows = is_iterable($disbursedRows ?? null) ? $disbursedRows : [];
                @endphp
                @if (count($disbursedProgramRows) > 0)
                  @foreach ($disbursedProgramRows as $programRow)
                    @php
                      $programName = trim((string) data_get($programRow, 'program', ''));
                      $programGrantees = (int) data_get($programRow, 'disbursed_scholars', 0);
                    @endphp
                    <tr>
                      <td class="ps-3">
                        <div class="program-name">
                          <i class="fas fa-award text-primary"></i>
                          <span class="text-sm">{{ $programName }}</span>
                        </div>
                      </td>
                      <td>
                        <span class="badge-grantee">{{ number_format($programGrantees) }} Grantees</span>
                      </td>
                      <td class="pe-3 text-end">
                        <a href="{{ route('scholarship-fund-report.index', array_filter(['program' => $programName, 'semester' => $semester])) }}" class="btn-action-report" data-bs-toggle="tooltip" data-bs-placement="top" title="Open Program">
                          <i class="fas fa-folder-open"></i>
                        </a>
                      </td>
                    </tr>
                  @endforeach
                @else
                  <tr>
                    <td colspan="3" class="text-center text-sm text-secondary py-4">No disbursed programs found for the selected filters.</td>
                  </tr>
                @endif
              @endif
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="fastFinalizeModal" tabindex="-1" role="dialog" aria-labelledby="fastFinalizeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-radius-xl shadow-lg border-0">
      <div class="modal-header p-4 bg-gradient-info border-0">
        <h5 class="modal-title text-white font-weight-bolder" id="fastFinalizeModalLabel"><i class="fas fa-bolt me-2"></i> Fast Finalize Batch</h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div id="fastFinalizeInfo" class="alert alert-light border-0 mb-4 text-xs">
          <p class="mb-1"><strong>Program:</strong> <span id="ff-program"></span></p>
          <p class="mb-1"><strong>Semester:</strong> <span id="ff-semester"></span></p>
          <p class="mb-0"><strong>Total to Disburse:</strong> ₱<span id="ff-amount"></span></p>
        </div>
        <form id="fastFinalizeForm">
          <input type="hidden" id="ff-batch-id">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label text-xs font-weight-bold">Disbursed Date</label>
              <input type="date" id="ff-disbursed-date" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label text-xs font-weight-bold">ADA No.</label>
              <input type="text" id="ff-ada-no" class="form-control" placeholder="ADA-2024-001" required>
            </div>
            <div class="col-md-6">
              <label class="form-label text-xs font-weight-bold">OR No.</label>
              <input type="text" id="ff-or-no" class="form-control" placeholder="0000001" maxlength="7" required>
            </div>
            <div class="col-md-6">
              <label class="form-label text-xs font-weight-bold">OR Date</label>
              <input type="date" id="ff-or-date" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
          </div>
          <p class="text-xxs text-muted mt-3"><i class="fas fa-info-circle me-1"></i> This will finalize ALL students in this batch using these details.</p>
        </form>
      </div>
      <div class="modal-footer p-4 border-0">
        <button type="button" class="btn btn-link text-secondary font-weight-bold" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn bg-gradient-info border-radius-lg px-4" onclick="submitFastFinalize()">Finalize Everything</button>
      </div>
    </div>
  </div>
</div>

@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Live Search Debounce
  let searchTimeout;
  const searchInput = document.getElementById('liveSearchInput');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        this.form.submit();
      }, 600); // Wait 600ms after last keystroke
    });

    // Put cursor at end of text
    const val = searchInput.value;
    searchInput.value = '';
    searchInput.value = val;
    searchInput.focus();
  }

  function openFastFinalize(id, program, semester, amount) {
    document.getElementById('ff-batch-id').value = id;
    document.getElementById('ff-program').innerText = program;
    document.getElementById('ff-semester').innerText = semester;
    document.getElementById('ff-amount').innerText = parseFloat(amount).toLocaleString(undefined, {minimumFractionDigits: 2});
    
    var myModal = new bootstrap.Modal(document.getElementById('fastFinalizeModal'));
    myModal.show();
  }

  function submitFastFinalize() {
    const id = document.getElementById('ff-batch-id').value;
    const data = {
      _token: '{{ csrf_token() }}',
      ada_no: document.getElementById('ff-ada-no').value,
      or_no: document.getElementById('ff-or-no').value,
      or_date: document.getElementById('ff-or-date').value,
      disbursed_date: document.getElementById('ff-disbursed-date').value
    };

    if (!data.ada_no || !data.or_no) {
      Swal.fire('Required', 'Please fill in all fields', 'warning');
      return;
    }

    Swal.fire({
      title: 'Are you sure?',
      text: "This will finalize all students in this batch!",
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#2152ff',
      confirmButtonText: 'Yes, Finalize All'
    }).then((result) => {
      if (result.isConfirmed) {
        Swal.showLoading();
        fetch(`/scholarship-system/disbursed-report/${id}/fast-finalize`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(res => {
          if (res.success) {
            const Toast = Swal.mixin({
              toast: true,
              position: 'top-end',
              showConfirmButton: false,
              timer: 3000,
              timerProgressBar: true
            });
            Toast.fire({
              icon: 'success',
              title: res.message
            }).then(() => {
              location.reload();
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Validation Error',
              text: res.message || 'Something went wrong',
              confirmButtonColor: '#003366'
            });
          }
        })
        .catch(err => {
          Swal.fire({
            icon: 'error',
            title: 'Communication Failure',
            text: 'Unable to connect to the server. Please check your connection.',
            confirmButtonColor: '#003366'
          });
        });
      }
    });
}
</script>
@endpush
