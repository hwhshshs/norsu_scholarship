@extends('layouts.user_type.auth')

@section('content')

<style>
    /* Absolute Sidebar Safety */
    @media (min-width: 1200px) {
        .main-content {
            margin-left: 17.125rem !important;
        }
    }
    
    /* Simple & Consistent Table */
    .student-table th, .student-table td {
        padding: 0.75rem 1rem !important;
        vertical-align: middle;
    }
    
    .student-table thead th {
        background-color: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
    }

    .text-xxs-bold {
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #67748e;
        letter-spacing: 0.05rem;
    }

    /* Hide default dropdown caret and animate our arrow */
    .dropdown-toggle::after {
        display: none !important;
    }
    
    .dropdown-toggle .fa-chevron-right {
        transition: transform 0.3s ease;
    }
    
    .dropdown-toggle[aria-expanded="true"] .fa-chevron-right {
        transform: rotate(90deg);
    }
</style>

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header pb-0 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="font-weight-bolder">Student Master List</h6>
            <p class="text-xs text-secondary mb-0">Manage and monitor all registered scholarship grantees</p>
        </div>
        <div class="dropdown">
            <button class="btn btn-primary-simple btn-icon-only mb-0 dropdown-toggle" type="button" id="studentActions" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-ellipsis-v me-1"></i><i class="fas fa-chevron-right text-xxs"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="studentActions">
                <li><a class="dropdown-item" href="{{ route('student-info.create') }}"><i class="fas fa-plus me-2"></i> Add Student</a></li>
                <li><button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#importModal"><i class="fas fa-upload me-2"></i> Import CSV</button></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="{{ route('student-info.template') }}"><i class="fas fa-download me-2"></i> Download Template</a></li>
            </ul>
        </div>
      </div>
      
      <div class="card-body px-0 pt-0 pb-2">
        <div class="p-3">
            <form action="{{ route('student-info.index') }}" method="GET" class="d-flex">
                <div class="input-group">
                    <span class="input-group-text text-body border-end-0"><i class="fas fa-search" aria-hidden="true"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search ID, Name, or Program..." value="{{ request('search') }}">
                </div>
                <button type="submit" class="btn btn-primary-simple btn-icon-only ms-2 mb-0" title="Search">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>

        <div class="table-responsive p-0">
          <table class="table align-items-center mb-0 student-table">
            <thead>
              <tr>
                <th class="text-xxs-bold">Student ID No.</th>
                <th class="text-xxs-bold">Last Name</th>
                <th class="text-xxs-bold">Given Name</th>
                <th class="text-xxs-bold text-center">M.I.</th>
                <th class="text-xxs-bold">Degree Program</th>
                <th class="text-xxs-bold text-center">Year Level</th>
                <th class="text-xxs-bold text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($students as $student)
              <tr>

                <td>
                  <p class="text-xs font-weight-bold mb-0">{{ $student->student_id_no }}</p>
                </td>
                <td>
                  <p class="text-xs font-weight-bold mb-0">{{ $student->last_name }}</p>
                </td>
                <td>
                  <p class="text-xs font-weight-bold mb-0">{{ $student->given_name }}</p>
                </td>
                <td class="text-center">
                  <p class="text-xs font-weight-bold mb-0">{{ $student->middle_initial ?: '-' }}</p>
                </td>
                <td>
                  <p class="text-xs font-weight-bold mb-0">{{ $student->degree_program }}</p>
                </td>
                <td class="text-center">
                  <p class="text-xs font-weight-bold mb-0">{{ $student->year_level }}</p>
                </td>
                <td class="text-center">
                  <button type="button" 
                          class="btn btn-icon-only btn-sm btn-primary-simple p-0 mb-0 shadow-sm" 
                          data-bs-toggle="modal" 
                          data-bs-target="#viewStudentModal"
                          data-student="{{ json_encode($student) }}"
                          onclick="viewStudent(this)">
                    <i class="fas fa-eye text-xs"></i>
                  </button>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="7" class="text-center py-4">
                  <p class="text-sm mb-0">No students found.</p>
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        
        <div class="px-3 py-3 d-flex justify-content-between align-items-center">
            <span class="text-xs text-secondary">Showing records {{ $students->firstItem() }} to {{ $students->lastItem() }}</span>
            <div>
                {{ $students->links() }}
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title font-weight-bold">Import Students</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('student-info.import') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label text-xs font-weight-bold">Select CSV File</label>
                <input class="form-control" type="file" name="file" accept=".csv" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary-simple btn-sm">Upload</button>
          </div>
      </form>
    </div>
  </div>
</div>

@push('js')
<script>
let currentStudentId = null;

function viewStudent(button) {
    const student = JSON.parse(button.getAttribute('data-student'));
    currentStudentId = student.id;
    
    document.getElementById('modalStudentName').innerText = `${student.last_name}, ${student.given_name} ${student.middle_initial ? student.middle_initial + '.' : ''}`;
    document.getElementById('modalStudentID').innerText = student.student_id_no || 'N/A';
    document.getElementById('modalAwardNo').innerText = student.tdp_tes_award_no || 'N/A';
    document.getElementById('modalProgram').innerText = student.degree_program || 'N/A';
    document.getElementById('modalScholarshipDisplay').innerText = student.scholarship_program || 'N/A';
    document.getElementById('modalYear').innerText = student.year_level || 'N/A';
    document.getElementById('modalEmail').innerText = student.email || 'N/A';
    document.getElementById('modalContact').innerText = student.contact_no || 'N/A';
    document.getElementById('modalPWD').innerText = student.pwd_no || 'N/A';
    document.getElementById('modalIP').innerText = student.ip_no || 'N/A';
    
    // Reset switcher
    hideSwitcher();
    
    // Fetch History
    fetchHistory(student.id);
    
    const fbContainer = document.getElementById('modalFBContainer');
    if (student.fb_link && student.fb_link !== 'N/A') {
        fbContainer.innerHTML = `<a href="${student.fb_link}" target="_blank" class="text-sm text-primary font-weight-bold"><i class="fab fa-facebook me-1"></i> View Facebook Profile</a>`;
    } else {
        fbContainer.innerHTML = `<p class="text-xs text-secondary mb-0">N/A</p>`;
    }
    
    document.getElementById('modalEditBtn').href = `/student-info/${student.id}/edit`;
}

function showSwitcher() {
    document.getElementById('scholarshipDisplayRow').classList.add('d-none');
    document.getElementById('scholarshipSwitcherRow').classList.remove('d-none');
    
    // Set current award no in the quick input
    const currentAward = document.getElementById('modalAwardNo').innerText;
    document.getElementById('quickAwardNo').value = currentAward === 'N/A' ? '' : currentAward;
}

function hideSwitcher() {
    document.getElementById('scholarshipDisplayRow').classList.remove('d-none');
    document.getElementById('scholarshipSwitcherRow').classList.add('d-none');
}

function fetchHistory(studentId) {
    const container = document.getElementById('historyTimeline');
    container.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin text-secondary"></i></div>';
    
    fetch('{{ url("student-info") }}/' + studentId + '/history')
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                container.innerHTML = '<p class="text-xs text-secondary text-center py-2 mb-0">No past scholarship entries found.</p>';
                return;
            }
            
            let html = '<div class="timeline-simple">';
            data.forEach(item => {
                const statusColor = item.status === 'Paid' ? 'text-success' : 'text-warning';
                html += `
                    <div class="timeline-item mb-2 pb-2 border-bottom border-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-chevron-right text-primary-simple text-xxs me-3"></i>
                                <div>
                                    <p class="text-xs font-weight-bold mb-0">${item.program}</p>
                                    <p class="text-xxs text-secondary mb-0">AY ${item.ay} | ${item.semester}</p>
                                </div>
                            </div>
                            <span class="text-xxs font-weight-bold ${statusColor}">${item.status}</span>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        })
        .catch(error => {
            console.error('Error fetching history:', error);
            container.innerHTML = '<p class="text-xs text-danger text-center py-2 mb-0">Failed to load history.</p>';
        });
}

function saveScholarship() {
    const newProgram = document.getElementById('quickScholarshipSelect').value;
    const oldProgram = document.getElementById('modalScholarshipDisplay').innerText;
    
    if (newProgram === oldProgram) {
        hideSwitcher();
        return;
    }

    Swal.fire({
        title: '<span class="text-sm font-weight-bold">Confirm Program Change?</span>',
        html: `<p class="text-xs mb-0">Switch to <b>${newProgram}</b>?</p>`,
        showCancelButton: true,
        confirmButtonColor: '#cb0c9f',
        cancelButtonColor: '#8392ab',
        confirmButtonText: 'Confirm',
        cancelButtonText: 'Cancel',
        width: '320px',
        padding: '1rem',
        buttonsStyling: true,
        customClass: {
            title: 'mt-2',
            confirmButton: 'btn btn-xs btn-primary-simple mx-1',
            cancelButton: 'btn btn-xs btn-outline-simple mx-1'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const saveBtn = document.querySelector('#scholarshipSwitcherRow button.btn-primary-simple');
            const newAwardNo = document.getElementById('quickAwardNo').value;
            
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch(`/student-info/${currentStudentId}/quick-update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    scholarship_program: newProgram,
                    tdp_tes_award_no: newAwardNo
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('modalScholarshipDisplay').innerText = newProgram;
                    document.getElementById('modalAwardNo').innerText = data.award_no;
                    hideSwitcher();
                    Swal.fire({
                        title: 'Updated!',
                        text: 'Scholarship program has been successfully changed.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', data.message || 'Could not update scholarship.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('System Error', 'A system error occurred while updating.', 'error');
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-check"></i>';
            });
        }
    });
}
</script>
@endpush

<!-- Simple View Modal -->
<div class="modal fade" id="viewStudentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary-simple text-white">
        <h6 class="modal-title" id="modalStudentName">Student Profile</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-6 mb-3">
                <label class="text-xxs font-weight-bold text-secondary">ID No.</label>
                <p class="text-sm font-weight-bold mb-0" id="modalStudentID"></p>
            </div>
            <div class="col-6 mb-3">
                <label class="text-xxs font-weight-bold text-secondary">Year</label>
                <p class="text-sm font-weight-bold mb-0" id="modalYear"></p>
            </div>
            <div class="col-6 mb-3">
                <label class="text-xxs font-weight-bold text-secondary">Degree Program</label>
                <p class="text-sm font-weight-bold mb-0" id="modalProgram"></p>
            </div>
            <div class="col-6 mb-3">
                <label class="text-xxs font-weight-bold text-secondary">Scholarship Program</label>
                <div id="scholarshipDisplayRow" class="d-flex align-items-center">
                    <p class="text-sm font-weight-bold text-info mb-0" id="modalScholarshipDisplay"></p>
                    <button onclick="showSwitcher()" class="btn btn-link text-primary-simple p-0 ms-2 mb-0" title="Quick Change">
                        <i class="fas fa-plus-circle text-xs"></i>
                    </button>
                </div>
                <div id="scholarshipSwitcherRow" class="d-none">
                    <div class="mt-2">
                        <label class="text-xxs font-weight-bold text-secondary mb-1">Select Program</label>
                        <select id="quickScholarshipSelect" class="form-control form-control-sm p-1 text-xs mb-2" style="height: 30px;">
                            <option value="N/A">N/A</option>
                            <option value="TDP-TES">TDP-TES</option>
                            <option value="CHED">CHED</option>
                            <option value="ACEF-GIAHEP">ACEF-GIAHEP</option>
                            <option value="CMSP">CMSP</option>
                        </select>
                        
                        <label class="text-xxs font-weight-bold text-secondary mb-1">Award No. (Optional)</label>
                        <input type="text" id="quickAwardNo" class="form-control form-control-sm text-xs mb-2" placeholder="Enter Award No.">
                        
                        <div class="d-flex justify-content-end mt-2">
                            <button onclick="hideSwitcher()" class="btn btn-xs btn-outline-simple mb-0 me-1">Cancel</button>
                            <button onclick="saveScholarship()" class="btn btn-xs btn-primary-simple mb-0"><i class="fas fa-check me-1"></i> Save</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 mb-3 border-top pt-2">
                <label class="text-xxs font-weight-bold text-secondary">Email</label>
                <p class="text-sm mb-0" id="modalEmail"></p>
            </div>
            <div class="col-12 mb-3">
                <label class="text-xxs font-weight-bold text-secondary">Contact</label>
                <p class="text-sm mb-0" id="modalContact"></p>
            </div>

            <div class="col-6 mb-3 border-top pt-2">
                <label class="text-xxs font-weight-bold text-secondary">PWD No.</label>
                <p class="text-sm mb-0" id="modalPWD"></p>
            </div>
            <div class="col-6 mb-3 border-top pt-2">
                <label class="text-xxs font-weight-bold text-secondary">IP No.</label>
                <p class="text-sm mb-0" id="modalIP"></p>
            </div>
            <div class="col-12 mb-3 border-top pt-2">
                <label class="text-xxs font-weight-bold text-secondary">Award No.</label>
                <p class="text-sm font-weight-bold mb-0" id="modalAwardNo"></p>
            </div>
            <div class="col-12 mb-3">
                <label class="text-xxs font-weight-bold text-secondary">Facebook Link</label>
                <div id="modalFBContainer"></div>
            </div>
            
            <div class="col-12 mb-3 border-top pt-2">
                <label class="text-xxs font-weight-bold text-secondary mb-2">Scholarship History</label>
                <div id="historyTimeline" style="max-height: 200px; overflow-y: auto;">
                    <!-- History items will be loaded here -->
                </div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <a href="#" id="modalEditBtn" class="btn btn-icon-only btn-sm btn-primary-simple" title="Edit Profile">
            <i class="fas fa-edit"></i>
        </a>
      </div>
    </div>
  </div>
</div>

@endsection