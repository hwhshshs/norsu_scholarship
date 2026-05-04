@extends('layouts.user_type.auth')

@section('content')

@php
  $isEdit = isset($student) && $student;
  $studentData = $isEdit ? (array) $student : [];
  $studentId = $studentData['id'] ?? null;
  $title = $isEdit ? 'Edit Student' : 'Add Student';

  $programOptionList = array_values(array_unique(array_filter(array_map('trim', (array) ($programOptions ?? [])), static function ($value) {
    return $value !== '';
  })));

  if (!in_array('Others', $programOptionList, true)) {
    $programOptionList[] = 'Others';
  }

  $defaultDegreeProgram = old('degree_program', trim((string) ($studentData['degree_program'] ?? '')));
  if ($defaultDegreeProgram === '') {
    $defaultDegreeProgram = trim((string) ($studentData['scholarship_program'] ?? ''));
  }
  if ($defaultDegreeProgram === '') {
    $defaultDegreeProgram = '';
  }

  $defaultScholarshipProgram = old('scholarship_program', trim((string) ($studentData['scholarship_program'] ?? '')));
  if ($defaultScholarshipProgram === '') {
    $defaultScholarshipProgram = $defaultDegreeProgram;
  }
  if ($defaultScholarshipProgram === '') {
    $defaultScholarshipProgram = '';
  }
  if ($defaultScholarshipProgram !== '' && !in_array($defaultScholarshipProgram, $programOptionList, true)) {
    $programOptionList[] = $defaultScholarshipProgram;
  }

  sort($programOptionList, SORT_NATURAL | SORT_FLAG_CASE);

  $defaultAcademicYear = old(
    'scholarship_academic_year',
    trim((string) ($studentData['scholarship_academic_year'] ?? ($academicYearOptions[0] ?? '2025-2026')))
  );

  $defaultPwdNo = old('pwd_no', trim((string) ($studentData['pwd_no'] ?? '')));
  if ($defaultPwdNo === '') {
    $defaultPwdNo = 'N/A';
  }

  $defaultIpNo = old('ip_no', trim((string) ($studentData['ip_no'] ?? '')));
  if ($defaultIpNo === '') {
    $defaultIpNo = 'N/A';
  }
@endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body d-md-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-1">{{ $title }}</h5>
          <p class="text-sm mb-0">Native student record form aligned to your scholarship schema.</p>
        </div>
        <a href="{{ route('scholarship-students.index') }}" class="btn btn-outline-dark mb-0">Back To Students</a>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        @if ($errors->any())
          <div class="alert alert-danger text-white" role="alert">
            <strong>Please fix the following:</strong>
            <ul class="mb-0 mt-2">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form method="POST" action="{{ $isEdit ? route('scholarship-students.update', $studentId) : route('scholarship-students.store') }}">
          @csrf
          @if ($isEdit)
            @method('PUT')
          @endif

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $studentData['last_name'] ?? '') }}" required />
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Given Name</label>
              <input type="text" name="given_name" class="form-control" value="{{ old('given_name', $studentData['given_name'] ?? '') }}" required />
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Middle Initial</label>
              <input type="text" name="middle_initial" class="form-control" value="{{ old('middle_initial', $studentData['middle_initial'] ?? '') }}" maxlength="10" />
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Student ID Number</label>
              <input type="text" name="student_id_no" id="student_id_input" class="form-control" value="{{ old('student_id_no', $studentData['student_id_no'] ?? '') }}" placeholder="YYYYXXXXX (e.g. 202300001)" maxlength="9" required />
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">TDP-TES Award No</label>
              <input type="text" name="tdp_tes_award_no" class="form-control" value="{{ old('tdp_tes_award_no', $studentData['tdp_tes_award_no'] ?? '') }}" required />
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Degree Program</label>
              <input type="text" name="degree_program" class="form-control" value="{{ $defaultDegreeProgram }}" required />

            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Scholarship Program</label>
              <select name="scholarship_program" id="scholarship_program_select" class="form-control" required>
                <option value="">Select Program</option>
                @foreach ($programOptionList as $programOption)
                  <option value="{{ $programOption }}" {{ $defaultScholarshipProgram === $programOption ? 'selected' : '' }}>{{ $programOption }}</option>
                @endforeach
              </select>
              <div id="scholarship_program_others_container" class="mt-2" style="display: none;">
                <label class="form-label text-xs">Specify Scholarship Program</label>
                <input type="text" id="scholarship_program_others" class="form-control" placeholder="Enter program name..." />
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Academic Year</label>
              <select name="scholarship_academic_year" class="form-control" required>
                <option value="">Select Academic Year</option>
                @foreach ($academicYearOptions as $academicYearOption)
                  <option value="{{ $academicYearOption }}" {{ $defaultAcademicYear === $academicYearOption ? 'selected' : '' }}>{{ $academicYearOption }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Scholarship Semester</label>
              <select name="scholarship_semester" class="form-control" required>
                <option value="">Select Semester</option>
                @foreach ($semesterOptions as $semesterOption)
                  <option value="{{ $semesterOption }}" {{ old('scholarship_semester', $studentData['scholarship_semester'] ?? '') === $semesterOption ? 'selected' : '' }}>{{ $semesterOption }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Year Level</label>
              <select name="year_level" class="form-control" required>
                <option value="">Select Year Level</option>
                @foreach ($yearLevelOptions as $yearLevelOption)
                  <option value="{{ $yearLevelOption }}" {{ old('year_level', $studentData['year_level'] ?? '') === $yearLevelOption ? 'selected' : '' }}>{{ $yearLevelOption }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Join Date</label>
              <input type="date" name="joindate" class="form-control" value="{{ old('joindate', $joinDateForm) }}" required />
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Facebook Link</label>
              <input type="text" name="fb_link" class="form-control" value="{{ old('fb_link', $studentData['fb_link'] ?? '') }}" required />
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Birthdate</label>
              <input type="date" name="birthdate" class="form-control" value="{{ old('birthdate', (!empty($studentData['birthdate']) && strtotime((string) $studentData['birthdate'])) ? date('Y-m-d', strtotime((string) $studentData['birthdate'])) : '') }}" />
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">School</label>
              <input type="text" name="school_name" class="form-control" value="{{ old('school_name', $studentData['school_name'] ?? '') }}" />
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Address</label>
              <input type="text" name="address" class="form-control" value="{{ old('address', $studentData['address'] ?? '') }}" />
            </div>
          </div>

          <div class="row">
            <div class="col-md-3 mb-3">
              <label class="form-label">Contact</label>
              <input type="text" name="contact" class="form-control" value="{{ old('contact', $studentData['contact'] ?? '') }}" placeholder="09123456789" maxlength="11" required />
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">PWD No</label>
              <input type="text" name="pwd_no" id="pwd_no_input" class="form-control" value="{{ $defaultPwdNo }}" placeholder="RR-PPMM-BBB-NNNNNNN" required />
              <div class="mt-2 p-2 bg-light border-radius-sm">
                <p class="text-xxs text-secondary mb-1"><strong>ID Structure:</strong></p>
                <ul class="list-unstyled mb-0" style="font-size: 0.65rem;">
                  <li class="text-muted">RR (Region), PP (Province), MM (City/Mun)</li>
                  <li class="text-muted">BBB (Barangay), NNNNNNN (Serial)</li>
                </ul>
              </div>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">IP No</label>
              <input type="text" name="ip_no" class="form-control" value="{{ $defaultIpNo }}" required />
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="emailid" class="form-control" value="{{ old('emailid', $studentData['emailid'] ?? '') }}" />
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Guardian Name</label>
              <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name', $studentData['guardian_name'] ?? '') }}" />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Guardian Contact</label>
              <input type="text" name="guardian_contact" class="form-control" value="{{ old('guardian_contact', $studentData['guardian_contact'] ?? '') }}" placeholder="09123456789" maxlength="11" />
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Initial Fees (Scholarship Amount)</label>
              <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="number" name="fees" class="form-control" value="{{ old('fees', $studentData['fees'] ?? '0.00') }}" step="0.01" min="0" />
              </div>
              <small class="text-xxs text-muted mt-1">The total amount this student has been billed for.</small>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Current Balance</label>
              <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="number" name="balance" class="form-control" value="{{ old('balance', $studentData['balance'] ?? '0.00') }}" step="0.01" min="0" />
              </div>
              <small class="text-xxs text-muted mt-1">Remaining amount to be paid/disbursed.</small>
            </div>
          </div>

          @if ($isEdit)
            <div class="alert alert-info text-white">
              Billing ledger summary in edit mode. Total Billing: {{ number_format($billingLedger['total'] ?? 0, 2) }}, Billing Rows: {{ $billingLedger['rows'] ?? 0 }}. Manual changes to balance above will override auto-calculations.
            </div>
          @else
            <div class="alert alert-info text-white">
              You can set initial amounts here, or leave them at 0 to manage them later via Billing Batches.
            </div>
          @endif

          <div class="mb-3">
            <label class="form-label">About Student</label>
            <textarea name="about" class="form-control" rows="3">{{ old('about', $studentData['about'] ?? '') }}</textarea>
          </div>

          <div class="d-flex">
            <button type="submit" class="btn bg-gradient-primary mb-0 me-2">Save Student</button>
            <button type="button" id="clear-form-btn" class="btn btn-outline-danger mb-0 me-2">
              <i class="fas fa-eraser me-1"></i> Clear Form
            </button>
            <a href="{{ route('scholarship-students.index') }}" class="btn btn-outline-secondary mb-0">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Enhanced Clear Form Confirmation Modal -->
<div class="modal fade" id="clearConfirmModal" tabindex="-1" role="dialog" aria-labelledby="clearConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem; overflow: hidden;">
      <div class="modal-body p-4 text-center">
        <!-- Pulsing Warning Icon -->
        <div class="mb-4">
          <div class="icon-shape bg-gradient-danger shadow-danger text-center border-radius-circle mx-auto d-flex align-items-center justify-content-center pulsing-icon" style="width: 70px; height: 70px;">
            <i class="fas fa-exclamation-triangle text-white text-lg"></i>
          </div>
        </div>
        
        <h4 class="font-weight-bolder text-dark mb-2">Clear All Data?</h4>
        <p class="text-secondary text-sm px-2">
          You're about to wipe everything you've typed. This will also delete your auto-saved backup.
        </p>
        
        <div class="mt-4 pt-2">
          <button type="button" id="confirm-clear-action-btn" class="btn bg-gradient-danger w-100 mb-2 py-3 shadow-sm click-fx-target">
            Yes, Clear Everything
          </button>
          <button type="button" class="btn btn-link text-secondary w-100 mb-0 py-2" data-bs-dismiss="modal">
            Nevermind, Keep It
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* Spring Entrance Animation */
#clearConfirmModal.show .modal-dialog {
    animation: modalSpring 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes modalSpring {
    from { transform: scale(0.8) translateY(20px); opacity: 0; }
    to { transform: scale(1) translateY(0); opacity: 1; }
}

/* Pulsing Icon Effect */
.pulsing-icon {
    animation: iconPulse 2s infinite;
}

@keyframes iconPulse {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(234, 6, 6, 0.4); }
    70% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(234, 6, 6, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(234, 6, 6, 0); }
}

/* High Intensity Backdrop Blur */
#clearConfirmModal + .modal-backdrop.show {
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
    background-color: rgba(0,0,0,0.5) !important;
}
</style>

@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.querySelector('form');
  const pwdInput = document.getElementById('pwd_no_input');
  const studentIdInput = document.getElementById('student_id_input');
  const scholarshipSelect = document.getElementById('scholarship_program_select');
  const scholarshipOthersContainer = document.getElementById('scholarship_program_others_container');
  const scholarshipOthersInput = document.getElementById('scholarship_program_others');
  const clearBtn = document.getElementById('clear-form-btn');

  const isEdit = {{ $isEdit ? 'true' : 'false' }};
  const studentId = {{ $isEdit ? $studentId : 'null' }};
  const storageKey = isEdit ? ('scholarship_student_form_edit_' + studentId) : 'scholarship_student_form_add';

  // 0. Toast helper
  const showToast = (message, type = 'success') => {
    const toastContainer = document.querySelector('.position-fixed.top-1.end-1');
    if (!toastContainer) return;
    
    const toastId = 'toast-' + Date.now();
    const icon = type === 'success' ? 'ni ni-check-bold text-success' : 'ni ni-notification-70 text-info';
    const title = type === 'success' ? 'Success' : 'Notification';
    
    const toastHtml = `
      <div class="toast fade show p-2 bg-white" role="alert" aria-live="assertive" id="${toastId}" aria-atomic="true">
        <div class="toast-header border-0">
          <i class="${icon} me-2"></i>
          <span class="me-auto font-weight-bold">${title}</span>
          <small class="text-body">Just now</small>
          <i class="fas fa-times text-md ms-3 cursor-pointer" data-bs-dismiss="toast" aria-label="Close"></i>
        </div>
        <hr class="horizontal dark m-0">
        <div class="toast-body text-sm">
          ${message}
        </div>
      </div>
    `;
    
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = toastHtml.trim();
    const toastEl = tempDiv.firstChild;
    toastContainer.appendChild(toastEl);
    
    const bsToast = new bootstrap.Toast(toastEl, { autohide: true, delay: 5000 });
    bsToast.show();
    
    toastEl.addEventListener('hidden.bs.toast', () => {
      toastEl.remove();
    });
  };

  // 1. Auto-format PWD input
  if (pwdInput) {
    pwdInput.addEventListener('input', function(e) {
      let value = e.target.value.toUpperCase();
      if (value === 'N' || value === 'NA' || value === 'N/' || value === 'N/A') return;
      let digits = value.replace(/\D/g, '');
      let formatted = '';
      if (digits.length > 0) {
        formatted += digits.substring(0, 2);
        if (digits.length > 2) {
          formatted += '-' + digits.substring(2, 6);
          if (digits.length > 6) {
            formatted += '-' + digits.substring(6, 9);
            if (digits.length > 9) {
              formatted += '-' + digits.substring(9, 16);
            }
          }
        }
      }
      e.target.value = formatted || value;
    });
  }

  // 1.2 Enforce 9-digit numeric Student ID
  if (studentIdInput) {
    studentIdInput.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, '');
      if (value.length > 9) value = value.substring(0, 9);
      e.target.value = value;
    });
  }

  // 1.3 Flexible PH Mobile format for contact fields
  const phoneInputs = document.querySelectorAll('input[name="contact"], input[name="guardian_contact"]');
  phoneInputs.forEach(input => {
    input.addEventListener('input', function(e) {
      // Allow numbers and the + sign
      let value = e.target.value.replace(/[^0-9+]/g, '');
      
      // If it starts with +, limit to 13 characters (+639XXXXXXXXX)
      if (value.startsWith('+')) {
        if (value.length > 13) value = value.substring(0, 13);
      } else {
        // Otherwise limit to 11 digits (09XXXXXXXXX)
        if (value.length > 11) value = value.substring(0, 11);
      }
      
      e.target.value = value;
    });

    // Auto-fix on blur (when user finishes typing)
    input.addEventListener('blur', function(e) {
      let value = e.target.value;
      // If user typed 10 digits starting with 9, add the 0 automatically
      if (value.length === 10 && value.startsWith('9')) {
        e.target.value = '0' + value;
        // Trigger input event to save to localStorage
        input.dispatchEvent(new Event('input'));
      }
    });
  });

  // 1.4 Prevent numbers in name fields
  const nameInputs = document.querySelectorAll('input[name="last_name"], input[name="given_name"], input[name="middle_initial"]');
  nameInputs.forEach(input => {
    input.addEventListener('input', function(e) {
      // Remove any numbers
      e.target.value = e.target.value.replace(/[0-9]/g, '');
    });
  });

  // 1.5 Scholarship Program "Others" logic
  if (scholarshipSelect) {
    const toggleOthers = function() {
      const val = scholarshipSelect.value;
      if (val === 'Others') {
        scholarshipOthersContainer.style.setProperty('display', 'block', 'important');
        scholarshipOthersInput.required = true;
        // Focus the input if the change was manual
        if (document.activeElement === scholarshipSelect) {
            setTimeout(() => scholarshipOthersInput.focus(), 100);
        }
      } else {
        scholarshipOthersContainer.style.setProperty('display', 'none', 'important');
        scholarshipOthersInput.required = false;
      }
    };

    scholarshipSelect.addEventListener('change', toggleOthers);
    // Initialize state
    toggleOthers();
  }

  // 2. Persistence Logic (Restore)
  const hasValidationErrors = {{ $errors->any() ? 'true' : 'false' }};
  const inputs = form.querySelectorAll('input, select, textarea');

  const restoreForm = function() {
    // Only restore if we don't have server-side validation errors (Laravel handles those)
    if (hasValidationErrors) return;

    const savedData = localStorage.getItem(storageKey);
    if (savedData) {
      try {
        const data = JSON.parse(savedData);
        inputs.forEach(input => {
          const name = input.name;
          if (name && data[name] !== undefined && name !== '_token' && name !== '_method') {
            if (input.type === 'checkbox' || input.type === 'radio') {
              input.checked = (data[name] === input.value);
            } else {
              input.value = data[name];
            }
            // Fire change event so other scripts (like formatting) can react
            input.dispatchEvent(new Event('change', { bubbles: true }));

            // Special handling for restored "Others" value
            if (name === 'scholarship_program' && input.id === 'scholarship_program_select') {
              if (data['scholarship_program_is_custom']) {
                input.value = data['scholarship_program_original_trigger'] || 'Others';
                scholarshipOthersInput.value = data['scholarship_program'];
                scholarshipOthersContainer.style.setProperty('display', 'block', 'important');
                scholarshipOthersInput.required = true;
              }
            }
          }
        });
      } catch (e) {
        console.error('Persistence: Restore failed', e);
      }
    }
  };

  // Restore immediately (with slight delay for stability)
  setTimeout(restoreForm, 100);

  // Restore on back/forward
  window.addEventListener('pageshow', function(event) {
    setTimeout(restoreForm, 100);
  });

  // 3. Persistence Logic (Save)
  const saveForm = function() {
    const data = {};
    inputs.forEach(input => {
      const name = input.name;
      if (name && name !== '_token' && name !== '_method') {
        if (input.type === 'checkbox' || input.type === 'radio') {
          if (input.checked) data[name] = input.value;
        } else {
          data[name] = input.value;
        }
      }
    });

    // Special handling for "Others" custom input in persistence
    if (scholarshipSelect && (scholarshipSelect.value === 'Others')) {
      data['scholarship_program'] = scholarshipOthersInput.value;
      data['scholarship_program_is_custom'] = true;
      data['scholarship_program_original_trigger'] = scholarshipSelect.value;
    }

    localStorage.setItem(storageKey, JSON.stringify(data));
  };

  inputs.forEach(input => {
    input.addEventListener('input', saveForm);
    input.addEventListener('change', saveForm);
  });

  // 4. Clear storage on successful submit & Handle "Others" name swap
  form.addEventListener('submit', function() {
    if (scholarshipSelect && (scholarshipSelect.value === 'Others')) {
      // Remove name from select and give it to the custom input
      scholarshipSelect.removeAttribute('name');
      scholarshipOthersInput.setAttribute('name', 'scholarship_program');
    }

    localStorage.removeItem(storageKey);
  });

  // 5. Manual Clear Logic
  if (clearBtn) {
    const clearModal = new bootstrap.Modal(document.getElementById('clearConfirmModal'));
    const confirmActionBtn = document.getElementById('confirm-clear-action-btn');

    clearBtn.addEventListener('click', function() {
      clearModal.show();
    });

    if (confirmActionBtn) {
      confirmActionBtn.addEventListener('click', function() {
        // Hide modal first
        clearModal.hide();

        // Perform clear
        form.reset();
        localStorage.removeItem(storageKey);
        
        // Handle custom fields and UI states
        if (scholarshipSelect) {
            scholarshipSelect.dispatchEvent(new Event('change'));
        }
        
        // Explicitly clear text values that might not be fully reset by form.reset() in some browsers
        form.querySelectorAll('input[type="text"], input[type="date"], input[type="email"], textarea').forEach(input => {
            input.value = '';
        });
        
        // Set defaults for specific fields
        const pwdInputVal = document.getElementById('pwd_no_input');
        if (pwdInputVal) pwdInputVal.value = 'N/A';
        
        const ipInput = form.querySelector('input[name="ip_no"]');
        if (ipInput) ipInput.value = 'N/A';

        const joinDateInput = form.querySelector('input[name="joindate"]');
        if (joinDateInput) {
            const today = new Date().toISOString().split('T')[0];
            joinDateInput.value = today;
        }

        // Final UI refresh
        if (typeof toggleOthers === 'function') toggleOthers();

        // Show toast confirmation
        showToast('Student form inputs and auto-save backup have been cleared.', 'success');
      });
    }
  }
});
</script>
@endpush
