@extends('layouts.user_type.auth')

@section('content')

<style>
    .scholar-name-wrapper {
        position: relative;
        cursor: pointer;
    }
    
    .profile-hover-card {
        display: none;
        position: absolute;
        bottom: 100%;
        left: 0;
        z-index: 1000;
        width: 280px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        padding: 15px;
        margin-bottom: 10px;
        border: 1px solid rgba(0,0,0,0.05);
        animation: slideUp 0.2s ease-out;
    }
    
    .scholar-name-wrapper:hover .profile-hover-card {
        display: block;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .detail-item {
        margin-bottom: 8px;
        display: flex;
        flex-direction: column;
    }
    
    .detail-label {
        font-size: 10px;
        text-transform: uppercase;
        color: #8392ab;
        font-weight: 700;
        margin-bottom: 1px;
    }
    
    .detail-value {
        font-size: 12px;
        color: #344767;
        font-weight: 600;
    }
</style>

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header pb-0 d-flex justify-content-between align-items-center">
        <h6>Billing Batch Details</h6>
        <div class="btn-group-icons">
            <a href="{{ route('billing.edit', $batch->id) }}" class="btn btn-icon-only btn-sm btn-primary-simple mb-0" title="Edit Batch">
                <i class="fas fa-edit"></i>
            </a>
            <a href="{{ url()->previous() }}" class="btn btn-icon-only btn-sm btn-outline-simple mb-0" title="Return">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
      </div>
      <div class="card-body">
        <div class="row">
            <div class="col-12 mb-4">
                <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3">Billing Info</h6>
                <ul class="list-group">
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Program:</strong> {{ $batch->program }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Semester:</strong> {{ $batch->semester }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">AY:</strong> {{ $batch->ay }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Batch:</strong> {{ $batch->batch ?? 'N/A' }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Region:</strong> {{ $batch->region ?? 'N/A' }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Date of Billing:</strong> {{ $batch->billing_date ? \Carbon\Carbon::parse($batch->billing_date)->format('M d, Y') : 'N/A' }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Amount:</strong> ₱{{ number_format($batch->amount, 2) }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Created By:</strong> {{ $batch->creator_name ?? 'System' }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm">
                    <strong class="text-dark">Official PDF:</strong> 
                    @if($batch->pdf_attachment)
                        <a href="{{ asset('storage/' . $batch->pdf_attachment) }}" target="_blank" class="text-primary font-weight-bold">
                            <i class="fas fa-file-pdf me-1"></i> Download / View Signed PDF
                        </a>
                    @else
                        <span class="text-secondary">None attached</span>
                    @endif
                  </li>
                  <li class="list-group-item border-0 ps-0 text-sm">
                    <strong class="text-dark">Disbursement PDF:</strong> 
                    @if($batch->disbursement_attachment)
                        <a href="{{ asset('storage/' . $batch->disbursement_attachment) }}" target="_blank" class="text-success font-weight-bold">
                            <i class="fas fa-file-pdf me-1"></i> Download / View Disbursement PDF
                        </a>
                    @else
                        <span class="text-secondary">None attached</span>
                    @endif
                  </li>
                  <li class="list-group-item border-0 ps-0 text-sm">
                    <strong class="text-dark">Disbursement Attachment:</strong> 
                    @if($batch->disbursement_scholar_file)
                        <a href="{{ asset('storage/' . $batch->disbursement_scholar_file) }}" target="_blank" class="text-info font-weight-bold">
                            <i class="fas fa-ellipsis-v me-1"></i><i class="fas fa-chevron-right me-1"></i> View Attachment
                        </a>
                    @else
                        <span class="text-secondary">None attached</span>
                    @endif
                  </li>
                </ul>
            </div>

        </div>

        @if(session('conflicts'))
        <div class="row" id="conflictPanel">
            <div class="col-12">
                <div class="card border-1 mb-4 shadow-sm">
                    <div class="card-header bg-primary-simple py-2">
                        <h6 class="text-white mb-0 text-sm"><i class="fas fa-shield-alt me-2"></i> ID Conflict Resolution Required</h6>
                    </div>
                    <div class="card-body p-3">
                        <p class="text-xs text-secondary mb-3">The following students are found in another record for the same period. Please select the correct program placement.</p>
                        <div class="table-responsive">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-xxs font-weight-bolder opacity-7 ps-2 text-uppercase">Student</th>
                                        <th class="text-xxs font-weight-bolder opacity-7 ps-2 text-uppercase">Current Record</th>
                                        <th class="text-xxs font-weight-bolder opacity-7 ps-2 text-uppercase">New Batch</th>
                                        <th class="text-xxs font-weight-bolder opacity-7 text-center text-uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="conflictTableBody">
                                    @foreach(session('conflicts') as $index => $conflict)
                                    <tr id="conflictRow{{ $index }}">
                                        <td class="text-xs">
                                            <div class="d-flex flex-column">
                                                <h6 class="mb-0 text-xs">{{ $conflict['name'] }}</h6>
                                                <p class="text-xxs text-secondary mb-0">{{ $conflict['id'] }}</p>
                                            </div>
                                        </td>
                                        <td class="text-xs font-weight-bold text-dark">{{ $conflict['current_program'] }}</td>
                                        <td class="text-xs font-weight-bold text-primary-simple">{{ $conflict['new_program'] }}</td>
                                        <td class="text-center">
                                            <div class="btn-group-icons justify-content-center">
                                                <button onclick="resolveConflict('{{ $index }}', 'switch', '{{ $conflict['id'] }}', '{{ $conflict['name'] }}', '{{ $conflict['current_program'] }}')" class="btn btn-icon-only btn-xs btn-primary-simple mb-0" title="Move to {{ $conflict['new_program'] }}">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button onclick="resolveConflict('{{ $index }}', 'ignore', '{{ $conflict['id'] }}', '{{ $conflict['name'] }}', '{{ $conflict['current_program'] }}')" class="btn btn-icon-only btn-xs btn-outline-simple mb-0" title="Skip for this batch">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
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

        <h6 class="text-uppercase text-body text-xs font-weight-bolder mt-4 mb-3">List of Scholars ({{ $batch->scholar_count }})</h6>
        <div class="table-responsive">
            <table class="table align-items-center mb-0">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">No.</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">ID No</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Student Name</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Year Level</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($scholars as $index => $scholar)
                    <tr>
                        <td class="text-sm ps-3">{{ $index + 1 }}</td>
                        <td>
                            <p class="text-sm font-weight-bold mb-0 text-dark">{{ $scholar->student_id_no ?? 'N/A' }}</p>
                        </td>
                        <td>
                            <p class="text-sm font-weight-bold mb-0 text-dark">
                                {{ $scholar->student_name }}
                                @if($scholar->fb_link && $scholar->fb_link !== 'N/A')
                                    <a href="{{ $scholar->fb_link }}" target="_blank" class="ms-1 text-primary" title="Facebook Profile">
                                        <i class="fab fa-facebook-square"></i>
                                    </a>
                                @endif
                            </p>
                        </td>
                        <td>
                            <span class="badge badge-sm bg-outline-simple text-primary-simple">
                                {{ $scholar->year_level ?: 'N/A' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-3">
                            @if($batch->scholar_file && str_ends_with($batch->scholar_file, '.pdf'))
                                <div class="p-3">
                                    <i class="fas fa-file-pdf text-danger fa-3x mb-3"></i>
                                    <p class="mb-0">A PDF Scholar List was uploaded for this batch.</p>
                                    <a href="{{ asset('storage/' . $batch->scholar_file) }}" target="_blank" class="btn btn-sm btn-outline-danger mt-2">
                                        View Scholar List PDF
                                    </a>
                                </div>
                            @else
                                No scholars uploaded.
                            @endif
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

@push('js')
<script>
function resolveConflict(index, action, id, name, oldProgram) {
    const actionText = action === 'switch' ? 'SWITCH' : 'IGNORE';
    const confirmMsg = action === 'switch' 
        ? `Are you sure you want to MOVE ${name} to this program and officially change their Master Profile?` 
        : `Are you sure you want to KEEP ${name} in ${oldProgram} and skip them for this batch?`;

    if (!confirm(confirmMsg)) return;

    fetch("{{ route('billing.resolve-conflict', $batch->id) }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            action: action,
            student_id_no: id,
            student_name: name,
            old_program: oldProgram
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById('conflictRow' + index);
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '0';
            row.style.transform = 'translateX(20px)';
            
            setTimeout(() => {
                row.remove();
                // Check if all gone
                if (document.querySelectorAll('[id^="conflictRow"]').length === 0) {
                    location.reload(); 
                }
            }, 300);
        } else {
            alert(data.error || 'Error resolving conflict.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('A system error occurred.');
    });
}
</script>
@endpush

@endsection
