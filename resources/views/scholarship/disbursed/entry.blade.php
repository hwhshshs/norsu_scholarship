@extends('layouts.user_type.auth')

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card mb-4 shadow-sm border-radius-xl">
      <div class="card-body d-md-flex align-items-center justify-content-between p-4">
        <div class="d-flex align-items-center">
          <a href="{{ url()->previous() }}" class="btn btn-icon-only btn-rounded btn-outline-secondary mb-0 me-3">
            <i class="fas fa-arrow-left"></i>
          </a>
          <div>
            <h5 class="mb-1">Disbursement Entry</h5>
            <p class="text-sm mb-0 text-secondary">Finalize payments for billed students and record ADA/OR details.</p>
          </div>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
          <a href="{{ route('scholarship-disbursed.import.form') }}" class="btn btn-sm btn-outline-success mb-0 border-radius-md">
            <i class="fas fa-file-import me-1"></i> Bulk Import
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

@if ($errors->any())
  <div class="row">
    <div class="col-12">
      <div class="alert alert-danger text-white" role="alert">
        <ul class="mb-0 ps-3">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>
@endif

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header pb-0">
        <h6 class="mb-0">Step 1: Select Batch</h6>
      </div>
      <div class="card-body pt-3">
        <form method="GET" action="{{ route('scholarship-disbursed.entry.form') }}" class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Scholarship Program</label>
            <select name="program" class="form-control" onchange="this.form.submit()">
              <option value="">Select Program</option>
              @foreach ($programOptions as $option)
                <option value="{{ $option }}" {{ $selectedProgram === $option ? 'selected' : '' }}>{{ $option }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Semester</label>
            <select name="semester" class="form-control" onchange="this.form.submit()">
              <option value="">Select Semester</option>
              @foreach ($semesterOptions as $option)
                <option value="{{ $option }}" {{ $selectedSemester === $option ? 'selected' : '' }}>{{ $option }}</option>
              @endforeach
            </select>
          </div>
          <input type="hidden" name="batch_id" value="{{ request('batch_id') }}">
          <div class="col-md-2">
            <button type="submit" class="btn bg-gradient-dark mb-0 w-100">Load Students</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@if ($resolvedBatch)
<div class="row">
  <div class="col-12">
    <form method="POST" action="{{ route('scholarship-disbursed.entry.store') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="program" value="{{ $selectedProgram }}" />
      <input type="hidden" name="semester" value="{{ $selectedSemester }}" />

      <div class="card mb-4 border-primary border-top">
        <div class="card-header pb-0">
          <h6 class="mb-0">Step 2: Payment Details (Batch: {{ $resolvedBatch->batch_label ?: 'Unlabeled' }})</h6>
        </div>
        <div class="card-body pt-3">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Disbursed Date</label>
              <input type="date" name="disbursed_date" class="form-control" value="{{ old('disbursed_date', date('Y-m-d')) }}" required />
            </div>
            <div class="col-md-3">
              <label class="form-label">ADA No.</label>
              <input type="text" name="ada_no" id="ada_no_input" class="form-control" value="{{ old('ada_no') }}" placeholder="e.g. ADA-2024-001" required />
            </div>
            <div class="col-md-3">
              <label class="form-label">OR No.</label>
              <input type="text" name="or_no" id="or_no_input" class="form-control" value="{{ old('or_no') }}" placeholder="e.g. 0000001" maxlength="7" required />
              <small class="text-xxs text-muted">Strict 7-digit ATP format (0000001-0500000)</small>
            </div>
            <div class="col-md-3">
              <label class="form-label">OR Date</label>
              <input type="date" name="or_date" class="form-control" value="{{ old('or_date', date('Y-m-d')) }}" required />
            </div>
            <div class="col-md-3">
              <label class="form-label font-weight-bold text-primary">Calculated Total</label>
              <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="number" step="0.01" name="disbursed_amount" id="total_amount_display" class="form-control font-weight-bold" value="0.00" />
              </div>
              <small class="text-xs text-muted">Auto-calculated from selection below.</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Attachments (Optional)</label>
              <input type="file" name="attachment_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" />
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center pb-0">
          <h6 class="mb-0">Step 3: Select Students to Finalize</h6>
          <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" id="selectAll" onclick="toggleAll(this)">
            <label class="form-check-label text-xs font-weight-bold" for="selectAll">Select All Pending</label>
          </div>
        </div>
        <div class="card-body px-0 pt-2 pb-0">
          <div class="table-responsive p-0">
            <table class="table align-items-center mb-0">
              <thead>
                <tr>
                  <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 5%"></th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Student ID</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Name</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Billed Amount</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Disburse Amount</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Remark</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($pendingStudents as $index => $student)
                  <tr>
                    <td class="text-center">
                      <div class="form-check d-flex justify-content-center">
                        <input class="form-check-input student-checkbox" type="checkbox" name="manual_students[{{ $index }}][selected]" value="1" onchange="updateTotal()">
                        <input type="hidden" name="manual_students[{{ $index }}][stdid]" value="{{ $student->stdid }}">
                      </div>
                    </td>
                    <td><p class="text-xs font-weight-bold mb-0">{{ $student->student_id_no }}</p></td>
                    <td><p class="text-xs mb-0">{{ $student->sname }}</p></td>
                    <td><p class="text-xs mb-0">₱{{ number_format($student->billed_amount, 2) }}</p></td>
                    <td>
                      <input type="number" step="0.01" name="manual_students[{{ $index }}][amount]" class="form-control form-control-sm student-amount" value="{{ $student->billed_amount }}" oninput="updateTotal()" style="width: 120px">
                    </td>
                    <td>
                      <input type="text" name="manual_students[{{ $index }}][remark]" class="form-control form-control-sm" placeholder="Optional remark">
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center py-4">
                      <div class="text-center">
                        <i class="fas fa-check-circle text-success mb-2" style="font-size: 2rem"></i>
                        <p class="text-sm mb-0">All billed students in this batch have already been finalized.</p>
                      </div>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
        @if (count($pendingStudents) > 0)
        <div class="card-footer">
          <button type="submit" class="btn bg-gradient-primary mb-0">
            <i class="fas fa-check-circle me-1"></i> Finalize Selected Students
          </button>
        </div>
        @endif
      </div>

      <div class="card mb-4 border-dashed border-2">
        <div class="card-body text-center p-4">
          <h6 class="text-secondary mb-2">Alternatively, upload a CSV file</h6>
          <p class="text-xs text-muted mb-3">If you have a large list, you can still use the bulk upload method.</p>
          <div class="col-md-6 mx-auto">
            <input type="file" name="grantee_csv" class="form-control form-control-sm" accept=".csv,.txt" />
            <small class="text-xxs text-muted mt-1 d-block">CSV will override the manual selection above.</small>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
  // ADA No. Input Masking
  const adaInput = document.getElementById('ada_no_input');
  if (adaInput) {
    adaInput.addEventListener('input', function(e) {
      let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
      
      // Auto-format: ADA-YYYY-NNN
      if (value.startsWith('ADA')) {
        let parts = [];
        parts.push(value.substring(0, 3)); // ADA
        
        if (value.length > 3) {
          parts.push(value.substring(3, 7)); // YYYY
        }
        
        if (value.length > 7) {
          parts.push(value.substring(7, 10)); // NNN
        }
        
        e.target.value = parts.join('-');
      }
    });
  }

  // OR No. ATP Padding & Range Check
  const orInput = document.getElementById('or_no_input');
  if (orInput) {
    orInput.addEventListener('blur', function(e) {
      let value = e.target.value.replace(/[^0-9]/g, '');
      if (value !== '') {
        // Pad to 7 digits
        let padded = value.padStart(7, '0');
        
        // Range check (0000001 - 0500000)
        let num = parseInt(padded, 10);
        if (num < 1 || num > 500000) {
          Swal.fire({
            icon: 'warning',
            title: 'Out of ATP Range',
            text: 'The OR No. must be between 0000001 and 0500000.',
            confirmButtonColor: '#003366'
          });
          e.target.classList.add('is-invalid');
        } else {
          e.target.classList.remove('is-invalid');
          e.target.value = padded;
        }
      }
    });

    orInput.addEventListener('input', function(e) {
      // Only allow numbers during typing
      e.target.value = e.target.value.replace(/[^0-9]/g, '');
    });
  }

  function toggleAll(master) {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
    updateTotal();
  }

  function updateTotal() {
    let total = 0;
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
      const cb = row.querySelector('.student-checkbox');
      const amountInput = row.querySelector('.student-amount');
      if (cb && cb.checked && amountInput) {
        total += parseFloat(amountInput.value) || 0;
      }
    });
    document.getElementById('total_amount_display').value = total.toFixed(2);
  }
</script>

@endif

@endsection
