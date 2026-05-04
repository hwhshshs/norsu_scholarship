@extends('layouts.user_type.auth')

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card mb-4 shadow-sm">
      <div class="card-body d-md-flex align-items-center justify-content-between p-4">
        <div class="d-flex align-items-center">
          <a href="{{ url()->previous() }}" class="btn btn-icon-only btn-rounded btn-outline-secondary mb-0 me-3">
            <i class="fas fa-arrow-left"></i>
          </a>
          <div>
            <h5 class="mb-1">Create Billing Batch</h5>
            <p class="text-sm mb-0">Record a new billing batch for scholarship grantees.</p>
          </div>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
          <a href="{{ route('scholarship-system.module', 'billing-entry') }}" class="btn btn-sm btn-outline-secondary mb-0">
            <i class="fas fa-history me-1"></i> Legacy
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

@if ($errors->any())
  <div class="row">
    <div class="col-12 mb-4">
        <div class="alert alert-danger alert-dismissible fade show text-white border-radius-xl" role="alert">
            <span class="alert-icon"><i class="fas fa-exclamation-triangle"></i></span>
            <span class="alert-text"><strong>Please correct the following errors:</strong></span>
            <ul class="mb-0 mt-2 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    </div>
  </div>
@endif

<div class="row">
  <div class="col-12">
    <form method="POST" action="{{ route('scholarship-billing.store') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="input_method" value="manual">

      <!-- Step 1: Batch Identification -->
      <div class="card mb-4 shadow-sm border-radius-xl">
        <div class="card-header pb-0 p-3">
          <div class="d-flex align-items-center">
            <div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle me-3" style="width: 32px; height: 32px;">
              <i class="fas fa-layer-group text-white opacity-10 text-xs"></i>
            </div>
            <div>
              <h6 class="mb-0">Step 1: Batch Identification</h6>
              <p class="text-xs mb-0 text-secondary">Set the program, semester, and total billing details.</p>
            </div>
          </div>
        </div>
        <div class="card-body p-3">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label text-xs font-weight-bold"><i class="fas fa-award text-primary me-1"></i> Scholarship Program</label>
              <select name="program" class="form-select border-radius-md @error('program') is-invalid @enderror" required>
                <option value="">Select Program</option>
                @foreach ($programOptions as $option)
                  <option value="{{ $option }}" {{ old('program') === $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
              </select>
              @error('program') <div class="invalid-feedback text-xxs">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label text-xs font-weight-bold"><i class="fas fa-graduation-cap text-primary me-1"></i> Semester</label>
              <select name="semester" class="form-select border-radius-md @error('semester') is-invalid @enderror" required>
                <option value="">Select Semester</option>
                @foreach ($semesterOptions as $option)
                  <option value="{{ $option }}" {{ old('semester') === $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
              </select>
              @error('semester') <div class="invalid-feedback text-xxs">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
              <label class="form-label text-xs font-weight-bold"><i class="fas fa-calendar-alt text-primary me-1"></i> Billing Date</label>
              <input type="date" name="billing_date" class="form-control border-radius-md @error('billing_date') is-invalid @enderror" value="{{ old('billing_date') }}" required />
              @error('billing_date') <div class="invalid-feedback text-xxs">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
              <label class="form-label text-xs font-weight-bold"><i class="fas fa-paper-plane text-primary me-1"></i> Date Submitted</label>
              <input type="date" name="submitted_date_to_ched" class="form-control border-radius-md @error('submitted_date_to_ched') is-invalid @enderror" value="{{ old('submitted_date_to_ched') }}" />
              @error('submitted_date_to_ched') <div class="invalid-feedback text-xxs">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label text-xs font-weight-bold"><i class="fas fa-file-invoice-dollar text-primary me-1"></i> Billing Amount</label>
              <div class="input-group">
                <span class="input-group-text border-radius-md bg-gray-100 font-weight-bold">₱</span>
                <input type="number" name="billing_amount" id="billing_amount_total" class="form-control border-radius-md font-weight-bold text-dark @error('billing_amount') is-invalid @enderror" step="0.01" min="0" value="{{ old('billing_amount', '0.00') }}" readonly />
                @error('billing_amount') <div class="invalid-feedback text-xxs">{{ $message }}</div> @enderror
              </div>
              <small class="text-xxs text-muted mt-1"><i class="fas fa-magic me-1"></i> Auto-calculated from student list below.</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 2: Student Entry -->
      <div class="card mb-4 shadow-sm border-radius-xl">
        <div class="card-header pb-0 p-3 d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center">
            <div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle me-3" style="width: 32px; height: 32px;">
              <i class="fas fa-users text-white opacity-10 text-xs"></i>
            </div>
            <div>
              <h6 class="mb-0">Step 2: Student Roster (Manual)</h6>
              <p class="text-xs mb-0 text-secondary">Search students by ID and assign billing amounts.</p>
            </div>
          </div>
          <a href="{{ route('scholarship-billing.import.form') }}" class="btn btn-sm btn-outline-success mb-0 border-radius-md">
             <i class="fas fa-file-import me-1"></i> Switch to Bulk Import
          </a>
        </div>
        <div class="card-body px-0 pt-3 pb-2">
          <div class="table-responsive">
            <table class="table align-items-center mb-0" id="manualTable">
              <thead class="bg-gray-100">
                <tr>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4" style="width: 25%">Student ID</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2" style="width: 30%">Full Name</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2" style="width: 20%">Amount</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2" style="width: 20%">Remark</th>
                  <th class="text-secondary opacity-7" style="width: 5%"></th>
                </tr>
              </thead>
              <tbody id="manualBody">
                <tr class="align-middle">
                  <td class="ps-4">
                    <div class="input-group input-group-sm">
                      <span class="input-group-text bg-white border-radius-md"><i class="fas fa-search text-xs"></i></span>
                      <input type="text" name="manual_students[0][student_id]" class="form-control student-id-lookup border-radius-md" placeholder="Type ID..." required onblur="lookupStudent(this)" />
                    </div>
                  </td>
                  <td>
                    <div class="d-flex px-2 py-1">
                      <div class="d-flex flex-column justify-content-center">
                        <h6 class="mb-0 text-xs student-name-display text-muted italic">Awaiting ID...</h6>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="input-group input-group-sm">
                      <span class="input-group-text bg-white border-radius-md font-weight-bold">₱</span>
                      <input type="number" name="manual_students[0][amount]" class="form-control manual-amount border-radius-md" step="0.01" placeholder="0.00" required oninput="updateTotalAmount()" />
                    </div>
                  </td>
                  <td>
                    <input type="text" name="manual_students[0][remark]" class="form-control form-control-sm border-radius-md" placeholder="Optional" />
                  </td>
                  <td class="text-center"></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="card-footer border-top p-3 d-flex justify-content-between align-items-center">
          <button type="button" class="btn btn-sm bg-gradient-primary mb-0 border-radius-md" onclick="addManualRow()">
            <i class="fas fa-plus-circle me-1"></i> Add Row
          </button>
          <span class="text-xs text-secondary font-weight-bold" id="rowCountLabel">1 Row(s)</span>
        </div>
      </div>

      <!-- Attachments & Submission -->
      <div class="card mb-4 shadow-sm border-radius-xl bg-gray-100 border-dashed">
        <div class="card-body p-3">
          <div class="row align-items-center">
            <div class="col-md-6">
              <label class="form-label text-xs font-weight-bold"><i class="fas fa-paperclip text-primary me-1"></i> Signed Billing Document (Optional)</label>
              <input type="file" name="signed_billing_doc" class="form-control border-radius-md shadow-none" accept=".pdf,.jpg,.jpeg,.png" />
            </div>
            <div class="col-md-6 d-flex justify-content-md-end mt-3 mt-md-0 gap-2">
              <a href="{{ route('scholarship-billing.index') }}" class="btn btn-sm btn-outline-secondary mb-0 border-radius-md">Cancel</a>
              <button type="submit" class="btn btn-sm bg-gradient-dark mb-0 border-radius-md px-4">
                <i class="fas fa-save me-1"></i> Save Billing Batch
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  let manualRowCount = 1;

  function updateRowLabel() {
    const rows = document.querySelectorAll('#manualBody tr').length;
    document.getElementById('rowCountLabel').textContent = `${rows} Row(s)`;
  }

  function addManualRow() {
    const body = document.getElementById('manualBody');
    const row = document.createElement('tr');
    row.className = 'align-middle';
    row.innerHTML = `
      <td class="ps-4">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white border-radius-md"><i class="fas fa-search text-xs"></i></span>
          <input type="text" name="manual_students[${manualRowCount}][student_id]" class="form-control student-id-lookup border-radius-md" placeholder="Type ID..." required onblur="lookupStudent(this)" />
        </div>
      </td>
      <td>
        <div class="d-flex px-2 py-1">
          <div class="d-flex flex-column justify-content-center">
            <h6 class="mb-0 text-xs student-name-display text-muted italic">Awaiting ID...</h6>
          </div>
        </div>
      </td>
      <td>
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white border-radius-md font-weight-bold">₱</span>
          <input type="number" name="manual_students[${manualRowCount}][amount]" class="form-control manual-amount border-radius-md" step="0.01" placeholder="0.00" required oninput="updateTotalAmount()" />
        </div>
      </td>
      <td>
        <input type="text" name="manual_students[${manualRowCount}][remark]" class="form-control form-control-sm border-radius-md" placeholder="Optional" />
      </td>
      <td class="text-center">
        <button type="button" class="btn btn-link text-danger p-0 mb-0" onclick="this.closest('tr').remove(); updateTotalAmount(); updateRowLabel();">
          <i class="fas fa-times-circle text-lg"></i>
        </button>
      </td>
    `;
    body.appendChild(row);
    manualRowCount++;
    updateRowLabel();
  }

  async function lookupStudent(input) {
    const idNo = input.value.trim();
    if (!idNo) return;

    const row = input.closest('tr');
    const nameDisplay = row.querySelector('.student-name-display');
    const programSelect = document.querySelector('select[name="program"]');
    const semesterSelect = document.querySelector('select[name="semester"]');
    
    try {
      const response = await fetch(`{{ url('scholarship-system/students/lookup') }}/${idNo}`);
      const result = await response.json();

      if (result.success) {
        const student = result.data;
        nameDisplay.textContent = student.name;
        nameDisplay.classList.remove('text-muted', 'italic');
        nameDisplay.classList.add('text-dark', 'font-weight-bold');

        // Auto-detect program
        if (student.program && student.program.trim() !== '') {
          const currentProgram = programSelect.value;
          if (!currentProgram) {
            Array.from(programSelect.options).forEach(option => {
              if (option.value.toLowerCase().trim() === student.program.toLowerCase().trim()) {
                programSelect.value = option.value;
              }
            });
          } else if (currentProgram.toLowerCase().trim() !== student.program.toLowerCase().trim()) {
            showLookupWarning('Program Mismatch', `Student ${student.name} is under "${student.program}", but you are creating a batch for "${currentProgram}".`);
          }
        }

        // Auto-detect semester
        if (student.semester && student.semester.trim() !== '') {
          const currentSemester = semesterSelect.value;
          if (!currentSemester) {
            Array.from(semesterSelect.options).forEach(option => {
              if (option.value.toLowerCase().trim() === student.semester.toLowerCase().trim()) {
                semesterSelect.value = option.value;
              }
            });
          } else if (currentSemester.toLowerCase().trim() !== student.semester.toLowerCase().trim()) {
            showLookupWarning('Semester Mismatch', `Student is usually enrolled in "${student.semester}", but you selected "${currentSemester}".`);
          }
        }
      } else {
        nameDisplay.textContent = 'Student not found';
        nameDisplay.classList.remove('text-dark', 'font-weight-bold');
        nameDisplay.classList.add('text-danger');
        
        Swal.fire({
          icon: 'error',
          title: 'Not Found',
          text: `Student with ID "${idNo}" was not found in the system.`,
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000
        });
      }
    } catch (error) {
      console.error('Lookup failed', error);
      nameDisplay.textContent = 'Error during lookup';
      nameDisplay.classList.add('text-warning');
    }
  }

  function showLookupWarning(title, text) {
    Swal.fire({
      icon: 'warning',
      title: title,
      text: text,
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 5000,
      timerProgressBar: true
    });
  }

  function updateTotalAmount() {
    let total = 0;
    document.querySelectorAll('.manual-amount').forEach(input => {
      const val = parseFloat(input.value);
      if (!isNaN(val)) total += val;
    });
    const billingAmountInput = document.getElementById('billing_amount_total');
    if (billingAmountInput) {
      billingAmountInput.value = total.toFixed(2);
    }
  }
</script>

@endsection
