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
            <h5 class="mb-1">Student Management</h5>
            <p class="text-sm mb-0">Manage scholar profiles, track classifications, and perform bulk updates.</p>
          </div>
        </div>
        <div class="mt-3 mt-md-0 d-flex flex-wrap gap-2 justify-content-md-end">
          <a href="{{ route('scholarship-students.create') }}" class="btn btn-sm bg-gradient-primary mb-0 border-radius-md px-4">
            <i class="fas fa-plus me-1"></i> Add Student
          </a>
          <a href="{{ route('scholarship-students.import.template') }}" class="btn btn-sm btn-outline-dark mb-0 border-radius-md">
            <i class="fas fa-download me-1"></i> Template
          </a>
          <a href="{{ route('scholarship-system.module', 'students') }}" class="btn btn-sm btn-outline-secondary mb-0 border-radius-md">
            <i class="fas fa-history me-1"></i> Legacy
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-4 mb-3 mb-md-0">
    <div class="card border-radius-xl shadow-sm border-start border-primary border-4">
      <div class="card-body p-3">
        <div class="d-flex align-items-center">
          <div class="icon icon-shape bg-gray-100 text-center rounded-circle me-3">
            <i class="fas fa-users text-primary opacity-10"></i>
          </div>
          <div>
            <p class="text-xxs mb-0 text-uppercase font-weight-bold text-secondary">Total Students</p>
            <h5 class="mb-0 font-weight-bolder">{{ number_format($stats['total']) }}</h5>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4 mb-3 mb-md-0">
    <div class="card border-radius-xl shadow-sm border-start border-success border-4">
      <div class="card-body p-3">
        <div class="d-flex align-items-center">
          <div class="icon icon-shape bg-gray-100 text-center rounded-circle me-3">
            <i class="fas fa-user-check text-success opacity-10"></i>
          </div>
          <div>
            <p class="text-xxs mb-0 text-uppercase font-weight-bold text-secondary">Active Scholars</p>
            <h5 class="mb-0 text-success font-weight-bolder">{{ number_format($stats['active']) }}</h5>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-radius-xl shadow-sm border-start border-danger border-4">
      <div class="card-body p-3">
        <div class="d-flex align-items-center">
          <div class="icon icon-shape bg-gray-100 text-center rounded-circle me-3">
            <i class="fas fa-user-times text-danger opacity-10"></i>
          </div>
          <div>
            <p class="text-xxs mb-0 text-uppercase font-weight-bold text-secondary">Inactive Records</p>
            <h5 class="mb-0 text-danger font-weight-bolder">{{ number_format($stats['inactive']) }}</h5>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card mb-4 shadow-sm border-radius-xl border-0">
      <div class="card-body p-3">
        <div class="row align-items-center">
          <!-- Left: Filter Section -->
          <div class="col-lg-7 d-flex flex-wrap align-items-center gap-2">
            <div class="d-flex align-items-center me-3">
               <i class="fas fa-filter text-primary me-2"></i>
               <h6 class="mb-0 text-sm font-weight-bold">Filters</h6>
            </div>
            <form method="GET" action="{{ route('scholarship-students.index') }}" class="d-flex flex-wrap gap-2 flex-grow-1">
              <div style="min-width: 200px;" class="flex-grow-1">
                <div class="input-group input-group-sm border-radius-md shadow-none border">
                  <span class="input-group-text bg-transparent border-0 pe-0"><i class="fas fa-search text-xs text-secondary"></i></span>
                  <input type="text" name="q" class="form-control border-0 ps-2" value="{{ $search }}" placeholder="Search Scholars..." />
                </div>
              </div>
              <div style="min-width: 140px;">
                <select name="status" class="form-select form-select-sm border-radius-md shadow-none border">
                  <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Status</option>
                  <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                  <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
              </div>
              <button type="submit" class="btn btn-sm mb-0 px-3 border-radius-md text-white" style="background: #003366;">
                Apply
              </button>
              <a href="{{ route('scholarship-students.index') }}" class="btn btn-sm btn-link text-secondary mb-0 px-2 border-radius-md">
                <i class="fas fa-times"></i>
              </a>
            </form>
          </div>

          <!-- Right: Import Section (Compact) -->
          <div class="col-lg-5 mt-3 mt-lg-0 border-start-lg ps-lg-4 d-flex align-items-center">
            <form method="POST" action="{{ route('scholarship-students.import') }}" enctype="multipart/form-data" class="d-flex align-items-center gap-2 w-100">
              @csrf
              <div class="flex-grow-1">
                <div class="position-relative">
                  <input type="file" name="students_csv" class="position-absolute top-0 start-0 opacity-0 w-100 h-100 cursor-pointer" accept=".csv" required onchange="updateFileName(this)" />
                  <div class="form-control form-control-sm border-radius-md d-flex align-items-center bg-gray-50 border-dashed" style="min-height: 31px;">
                    <i class="fas fa-file-csv text-success me-2"></i>
                    <span id="file-name-display" class="text-xxs text-secondary font-weight-bold text-truncate" style="max-width: 150px;">Choose CSV...</span>
                  </div>
                </div>
              </div>
              <button type="submit" class="btn btn-sm mb-0 px-3 border-radius-md" style="background: #FFD700; color: #003366;">
                <i class="fas fa-upload me-1"></i> Import
              </button>
              <i class="fas fa-info-circle text-info text-xs" data-bs-toggle="tooltip" title="Smart Fill: Only updates missing info."></i>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.border-dashed {
  border-style: dashed !important;
}
.border-start-lg {
    border-left: 1px solid #dee2e6;
}
@media (max-width: 991.98px) {
    .border-start-lg {
        border-left: none;
        border-top: 1px solid #dee2e6;
        padding-top: 1rem;
    }
}
</style>

<script>
function updateFileName(input) {
  const display = document.getElementById('file-name-display');
  if (input.files && input.files.length > 0) {
    display.textContent = input.files[0].name;
    display.classList.remove('text-secondary');
    display.classList.add('text-dark');
  } else {
    display.textContent = 'Choose CSV...';
    display.classList.remove('text-dark');
    display.classList.add('text-secondary');
  }
}
</script>

<div class="row">
  <div class="col-12">
    <div class="card shadow-sm border-radius-xl overflow-hidden">
      <div class="card-header pb-0 p-3 bg-gray-100">
        <div class="d-flex justify-content-between align-items-center">
          <h6 class="mb-0 text-dark font-weight-bold">Scholarship Roster</h6>
          <span class="badge badge-sm bg-white text-dark shadow-xs border-radius-md">{{ number_format(count($students)) }} Students</span>
        </div>
      </div>
      <div class="card-body px-0 pt-0 pb-2">
        <div class="table-responsive p-0">
          <table class="table table-hover align-items-center mb-0">
            <thead class="bg-gray-100">
              <tr>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4" style="width: 15%">ID No.</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2" style="width: 30%">Full Name</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center" style="width: 25%">Profile Status</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($students as $student)
                @php
                  $isInactive = (string) ($student->delete_status ?? '0') === '1';
                @endphp
                <tr class="{{ $isInactive ? 'opacity-7 bg-gray-50' : '' }}">
                  <td class="ps-4">
                    <p class="text-xs font-weight-bold mb-0 text-dark">{{ $student->student_id_no }}</p>
                  </td>
                  <td>
                    <h6 class="mb-0 text-sm font-weight-bold">{{ $student->last_name }}, {{ $student->given_name }}</h6>
                  </td>
                  <td class="text-center">
                    @php
                      $completeness = \App\Support\ScholarshipMonitoring::isProfileComplete($student);
                    @endphp
                    <div class="d-flex flex-column align-items-center">
                      <a href="javascript:;" class="badge badge-sm {{ $completeness['is_complete'] ? 'bg-gradient-success' : 'bg-gradient-warning' }} border-0 shadow-xs cursor-pointer mb-0" 
                         data-bs-toggle="modal" data-bs-target="#studentModal{{ $student->id }}"
                         title="Click to view full profile">
                        <i class="fas {{ $completeness['is_complete'] ? 'fa-check-circle' : 'fa-exclamation-triangle' }} me-1"></i> 
                        {{ $completeness['is_complete'] ? 'COMPLETE' : 'INCOMPLETE' }}
                      </a>
                      @if (!$completeness['is_complete'])
                        <p class="text-xxs text-secondary mb-0 mt-1">{{ $completeness['completion_percentage'] }}% Done</p>
                      @endif
                    </div>

                    </div>
                  </td>
                  <td class="align-middle text-center">
                    <div class="d-flex justify-content-center gap-2 pe-3">
                      <a href="javascript:;" class="btn btn-icon-only btn-rounded btn-outline-info mb-0" 
                         data-bs-toggle="modal" data-bs-target="#studentModal{{ $student->id }}" title="View Profile">
                        <i class="fas fa-eye text-xs"></i>
                      </a>
                      <a href="{{ route('scholarship-students.edit', $student->id) }}" class="btn btn-icon-only btn-rounded btn-outline-warning mb-0" title="Edit">
                        <i class="fas fa-pen text-xs"></i>
                      </a>
                      <form method="POST" action="{{ route('scholarship-students.toggle-status', $student->id) }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="target_status" value="{{ $isInactive ? '0' : '1' }}" />
                        <button type="submit" class="btn btn-icon-only btn-rounded {{ $isInactive ? 'btn-outline-success' : 'btn-outline-dark' }} mb-0" title="{{ $isInactive ? 'Activate' : 'Deactivate' }}" onclick="confirmAction(event, '{{ $isInactive ? 'Activate Student?' : 'Deactivate Student?' }}', 'Are you sure you want to {{ $isInactive ? 'reactivate' : 'deactivate' }} this student record?');">
                          <i class="fas {{ $isInactive ? 'fa-check' : 'fa-ban' }} text-xs"></i>
                        </button>
                      </form>
                      <form method="POST" action="{{ route('scholarship-students.remove', $student->id) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-icon-only btn-rounded btn-outline-danger mb-0" title="Delete" onclick="confirmAction(event, '{{ $isInactive ? 'Delete Student?' : 'Move to Inactive?' }}', '{{ $isInactive ? 'Permanently remove from system?' : 'Move to inactive list?' }}');">
                          <i class="fas fa-trash text-xs"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center py-5">
                    <i class="fas fa-user-slash text-secondary text-3xl mb-3 d-block"></i>
                    <p class="text-secondary font-weight-bold">No student records found matching your filters.</p>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  function confirmAction(e, title, text) {
    e.preventDefault();
    const form = e.target.closest('form');
    Swal.fire({
      title: title,
      text: text,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#003366',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, proceed!',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  }
</script>

@foreach ($students as $student)
  @php
    $completeness = \App\Support\ScholarshipMonitoring::isProfileComplete($student);
  @endphp
  <div class="modal fade" id="studentModal{{ $student->id }}" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content border-radius-xl shadow-lg border-0">
        <div class="modal-header p-4 border-0">
          <div class="d-flex align-items-center">
            <div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle me-3">
              <i class="fas fa-user-graduate text-white opacity-10"></i>
            </div>
            <div class="text-start">
              <h6 class="modal-title font-weight-bold text-dark mb-0">{{ $student->last_name }}, {{ $student->given_name }}</h6>
              <p class="text-xs text-secondary mb-0">ID: {{ $student->student_id_no }}</p>
            </div>
          </div>
          <button type="button" class="btn btn-link text-dark ms-auto p-0 mb-0" data-bs-dismiss="modal">
            <i class="fas fa-times text-lg"></i>
          </button>
        </div>
        <div class="modal-body p-4 pt-0 text-start">
          <div class="bg-gray-100 border-radius-lg p-3 mb-4">
            <div class="row align-items-center">
              <div class="col-8">
                <label class="text-xxs font-weight-bold text-uppercase text-secondary mb-1 d-block opacity-7">Profile Completeness</label>
                <div class="progress progress-xs mb-0">
                  <div class="progress-bar {{ $completeness['is_complete'] ? 'bg-success' : 'bg-warning' }}" role="progressbar" style="width: {{ $completeness['completion_percentage'] }}%"></div>
                </div>
              </div>
              <div class="col-4 text-end">
                <span class="text-xs font-weight-bold text-dark">{{ $completeness['completion_percentage'] }}%</span>
              </div>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-12 mb-3">
              <label class="text-xxs font-weight-bold text-uppercase text-secondary mb-1 d-block opacity-7"><i class="fas fa-graduation-cap me-1"></i> Academic Program</label>
              <p class="text-sm font-weight-bold text-dark mb-0">{{ $student->degree_program ?: 'Not specified' }}</p>
              <p class="text-xs text-secondary mb-0">{{ $student->year_level ?: 'Year N/A' }}</p>
            </div>
            <div class="col-6">
              <label class="text-xxs font-weight-bold text-uppercase text-secondary mb-1 d-block opacity-7"><i class="fas fa-id-card me-1"></i> PWD No.</label>
              <p class="text-sm font-weight-bold text-dark">{{ $student->pwd_no ?: 'N/A' }}</p>
            </div>
            <div class="col-6">
              <label class="text-xxs font-weight-bold text-uppercase text-secondary mb-1 d-block opacity-7"><i class="fas fa-id-badge me-1"></i> IP No.</label>
              <p class="text-sm font-weight-bold text-dark">{{ $student->ip_no ?: 'N/A' }}</p>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-12 mb-3">
              <label class="text-xxs font-weight-bold text-uppercase text-secondary mb-1 d-block opacity-7"><i class="fas fa-envelope me-1"></i> Contact Details</label>
              <div class="d-flex align-items-center mb-1">
                <span class="text-sm text-dark">{{ $student->emailid ?: 'No email' }}</span>
              </div>
              <div class="d-flex align-items-center">
                <span class="text-sm text-dark">{{ $student->contact ?: 'No phone' }}</span>
              </div>
            </div>
            <div class="col-12">
              <label class="text-xxs font-weight-bold text-uppercase text-secondary mb-1 d-block opacity-7"><i class="fab fa-facebook me-1"></i> Social Presence</label>
              @php
                $rawLink = (string) ($student->fb_link ?? '');
                $hasLink = !empty($rawLink) && strtolower($rawLink) !== 'n/a' && strtolower($rawLink) !== 'none';
                $formattedLink = $hasLink ? (str_starts_with($rawLink, 'http') ? $rawLink : 'https://' . $rawLink) : '';
              @endphp
              @if ($hasLink)
                <a href="{{ $formattedLink }}" target="_blank" class="btn btn-sm btn-outline-info w-100 border-radius-md mb-0">
                  <i class="fab fa-facebook me-2"></i> Visit Facebook Profile
                </a>
              @else
                <p class="text-sm text-secondary mb-0 italic text-center py-2 bg-gray-50 border-radius-md">No social link connected.</p>
              @endif
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 p-4 pt-0 mt-2">
          <button type="button" class="btn btn-sm btn-outline-secondary mb-0 me-2" data-bs-dismiss="modal">Close</button>
          <a href="{{ route('scholarship-students.edit', $student->id) }}" class="btn btn-sm bg-gradient-primary mb-0">
            <i class="fas fa-edit me-1"></i> Edit Profile
          </a>
        </div>
      </div>
    </div>
  </div>
@endforeach

@endsection
