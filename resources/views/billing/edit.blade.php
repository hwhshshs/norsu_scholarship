@extends('layouts.user_type.auth')

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header pb-0">
        <h6>Edit Billing Batch</h6>
      </div>
      <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger text-white pb-0">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @php 
            $isDisburseMode = request('mode') == 'disburse';
        @endphp

        <script>
            function confirmRemove(type) {
                document.getElementById('remove-action-' + type).style.display = 'none';
                document.getElementById('remove-confirm-' + type).style.display = 'inline';
            }

            function cancelRemove(type) {
                document.getElementById('remove-action-' + type).style.display = 'inline';
                document.getElementById('remove-confirm-' + type).style.display = 'none';
            }

            async function executeRemove(type) {
                try {
                    const response = await fetch("{{ route('billing.remove-attachment', $batch->id) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ type: type })
                    });
                    
                    const resultJson = await response.json();
                    if (resultJson.success) {
                        const container = document.getElementById('file-container-' + type);
                        if (container) {
                            container.remove(); // Brute force remove from DOM
                        }
                        showToast('success', 'File removed successfully!');
                    } else {
                        showToast('error', 'Error: ' + resultJson.message);
                        cancelRemove(type);
                    }
                } catch (error) {
                    console.error('Removal error:', error);
                    showToast('error', 'An error occurred.');
                    cancelRemove(type);
                }
            }
        </script>

        <form action="{{ route('billing.update', $batch->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            @php 
                $readonlyAttr = $isDisburseMode ? 'readonly style="pointer-events: none; background-color: #f8f9fa;"' : '';
                $disabledAttr = $isDisburseMode ? 'style="pointer-events: none; background-color: #f8f9fa;"' : '';
            @endphp

            <!-- Billing Information (Always Visible) -->
            <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3 mt-2 d-flex justify-content-between align-items-center">
                Billing Information
                @if($isDisburseMode)
                    <span class="badge bg-secondary text-xxs">Read-Only View</span>
                @endif
            </h6>
            <div class="row bg-gray-100 p-3 rounded mb-4">
                <div class="col-12 mb-3">
                    <label class="form-label">Program *</label>
                    <div style="{{ $isDisburseMode ? 'pointer-events: none;' : '' }}">
                        <select class="form-control" name="program" id="program" required {{ $isDisburseMode ? 'style=background-color:#f8f9fa;' : '' }}>
                            <option value="TDP-TES" {{ $batch->program == 'TDP-TES' ? 'selected' : '' }}>TDP-TES</option>
                            <option value="CHED" {{ $batch->program == 'CHED' ? 'selected' : '' }}>CHED</option>
                            <option value="ACEF-GIAHEP" {{ $batch->program == 'ACEF-GIAHEP' ? 'selected' : '' }}>ACEF-GIAHEP</option>
                            <option value="CMSP" {{ $batch->program == 'CMSP' ? 'selected' : '' }}>CMSP</option>
                        </select>
                    </div>
                    @error('program') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Semester *</label>
                    <input type="text" name="semester" class="form-control" value="{{ old('semester', $batch->semester) }}" required {!! $readonlyAttr !!}>
                    @error('semester') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">AY *</label>
                    <input type="text" name="ay" class="form-control" value="{{ old('ay', $batch->ay) }}" required {!! $readonlyAttr !!}>
                    @error('ay') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Batch</label>
                    <input type="text" name="batch" class="form-control" value="{{ old('batch', $batch->batch) }}" {!! $readonlyAttr !!}>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Region</label>
                    <input type="text" name="region" class="form-control" value="{{ old('region', $batch->region) }}" {!! $readonlyAttr !!}>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Date of Billing</label>
                    <input type="date" name="billing_date" class="form-control" value="{{ old('billing_date', $batch->billing_date) }}" {!! $readonlyAttr !!}>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount', $batch->amount) }}" {!! $readonlyAttr !!}>
                </div>
                @if(!$isDisburseMode)
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Update Official Signed PDF</label>
                        <input type="file" name="pdf_attachment" class="form-control" accept=".pdf">
                        @if($batch->pdf_attachment)
                            <div class="mt-2 text-xs d-flex align-items-center" id="file-container-pdf_attachment">
                                <a href="{{ asset('storage/' . $batch->pdf_attachment) }}" target="_blank" class="text-primary font-weight-bold">
                                    <i class="fas fa-file-pdf me-1"></i> View Current PDF
                                </a>
                                <span class="mx-2 text-secondary">|</span>
                                <span id="remove-action-pdf_attachment">
                                    <a href="javascript:void(0)" onclick="confirmRemove('pdf_attachment')" class="text-danger font-weight-bold">
                                        <i class="fas fa-trash-alt me-1"></i> Remove
                                    </a>
                                </span>
                                <span id="remove-confirm-pdf_attachment" style="display:none">
                                    <span class="text-secondary">Confirm?</span>
                                    <a href="javascript:void(0)" onclick="executeRemove('pdf_attachment')" class="text-danger font-weight-bold mx-1">Yes</a>
                                    <span class="text-secondary">/</span>
                                    <a href="javascript:void(0)" onclick="cancelRemove('pdf_attachment')" class="text-secondary font-weight-bold ms-1">No</a>
                                </span>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Update Scholars (CSV or PDF)</label>
                        <input type="file" name="scholar_file" class="form-control" accept=".csv,.txt,.pdf">
                        <small class="text-muted">Upload CSV for extraction or PDF for attachment</small>
                        @if($batch->scholar_file)
                            <div class="mt-2 text-xs d-flex align-items-center" id="file-container-scholar_file">
                                <a href="{{ asset('storage/' . $batch->scholar_file) }}" target="_blank" class="text-primary font-weight-bold">
                                    <i class="fas fa-file-pdf me-1"></i> View Current List
                                </a>
                                <span class="mx-2 text-secondary">|</span>
                                <span id="remove-action-scholar_file">
                                    <a href="javascript:void(0)" onclick="confirmRemove('scholar_file')" class="text-danger font-weight-bold">
                                        <i class="fas fa-trash-alt me-1"></i> Remove
                                    </a>
                                </span>
                                <span id="remove-confirm-scholar_file" style="display:none">
                                    <span class="text-secondary">Confirm?</span>
                                    <a href="javascript:void(0)" onclick="executeRemove('scholar_file')" class="text-danger font-weight-bold mx-1">Yes</a>
                                    <span class="text-secondary">/</span>
                                    <a href="javascript:void(0)" onclick="cancelRemove('scholar_file')" class="text-secondary font-weight-bold ms-1">No</a>
                                </span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            @if($isDisburseMode)
                <!-- Disbursement Details (Only visible in Disburse mode) -->
                <h6 id="disbursement-section" class="text-uppercase text-body text-xs font-weight-bolder mb-3">Disbursement Details</h6>
                <div class="row bg-gray-100 p-3 rounded mb-4">
                    <div class="col-12 mb-3">
                        <label class="form-label">Date on ADA Details</label>
                        <input type="date" name="ada_date" class="form-control" value="{{ old('ada_date', $batch->ada_date) }}">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">ADA No.</label>
                        <input type="text" name="ada_no" class="form-control" value="{{ old('ada_no', $batch->ada_no) }}">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">OR Number</label>
                        <input type="text" name="or_number" class="form-control" value="{{ old('or_number', $batch->or_number) }}">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">OR Date</label>
                        <input type="date" name="or_date" class="form-control" value="{{ old('or_date', $batch->or_date) }}">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label d-flex justify-content-between">
                            Status (No. of Students Disbursed)
                            <a href="javascript:void(0)" onclick="document.getElementsByName('disbursed_count')[0].value = '{{ $batch->scholar_count }}'" class="text-xs text-primary font-weight-bold">
                                <i class="fas fa-magic me-1"></i> Auto-fill Total ({{ $batch->scholar_count }})
                            </a>
                        </label>
                        <input type="number" name="disbursed_count" class="form-control" value="{{ old('disbursed_count', $batch->disbursed_count ?: $batch->scholar_count) }}">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Official Signed PDF Attachment</label>
                            <input type="file" name="disbursement_attachment" class="form-control" accept=".pdf">
                            <small class="text-muted">For official records (signed PDF document)</small>
                            @if($batch->disbursement_attachment)
                                <div class="mt-2 text-xs d-flex align-items-center" id="file-container-disbursement_attachment">
                                    <a href="{{ asset('storage/' . $batch->disbursement_attachment) }}" target="_blank" class="text-primary font-weight-bold">
                                        <i class="fas fa-file-pdf me-1"></i> View Current PDF
                                    </a>
                                    <span class="mx-2 text-secondary">|</span>
                                    <span id="remove-action-disbursement_attachment">
                                        <a href="javascript:void(0)" onclick="confirmRemove('disbursement_attachment')" class="text-danger font-weight-bold">
                                            <i class="fas fa-trash-alt me-1"></i> Remove
                                        </a>
                                    </span>
                                    <span id="remove-confirm-disbursement_attachment" style="display:none">
                                        <span class="text-secondary">Confirm?</span>
                                        <a href="javascript:void(0)" onclick="executeRemove('disbursement_attachment')" class="text-danger font-weight-bold mx-1">Yes</a>
                                        <span class="text-secondary">/</span>
                                        <a href="javascript:void(0)" onclick="cancelRemove('disbursement_attachment')" class="text-secondary font-weight-bold ms-1">No</a>
                                    </span>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Scholar List (CSV or PDF)</label>
                            <input type="file" name="disbursement_scholar_file" class="form-control" accept=".csv,.txt,.pdf">
                            <small class="text-muted">Upload CSV for extraction or PDF for attachment</small>
                            @if($batch->disbursement_scholar_file)
                                <div class="mt-2 text-xs d-flex align-items-center" id="file-container-disbursement_scholar_file">
                                    <a href="{{ asset('storage/' . $batch->disbursement_scholar_file) }}" target="_blank" class="text-primary font-weight-bold">
                                        <i class="fas fa-file-pdf me-1"></i> View Current List
                                    </a>
                                    <span class="mx-2 text-secondary">|</span>
                                    <span id="remove-action-disbursement_scholar_file">
                                        <a href="javascript:void(0)" onclick="confirmRemove('disbursement_scholar_file')" class="text-danger font-weight-bold">
                                            <i class="fas fa-trash-alt me-1"></i> Remove
                                        </a>
                                    </span>
                                    <span id="remove-confirm-disbursement_scholar_file" style="display:none">
                                        <span class="text-secondary">Confirm?</span>
                                        <a href="javascript:void(0)" onclick="executeRemove('disbursement_scholar_file')" class="text-danger font-weight-bold mx-1">Yes</a>
                                        <span class="text-secondary">/</span>
                                        <a href="javascript:void(0)" onclick="cancelRemove('disbursement_scholar_file')" class="text-secondary font-weight-bold ms-1">No</a>
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <!-- Hidden inputs to preserve disbursement data when editing registration -->
                <input type="hidden" name="ada_date" value="{{ $batch->ada_date }}">
                <input type="hidden" name="ada_no" value="{{ $batch->ada_no }}">
                <input type="hidden" name="or_number" value="{{ $batch->or_number }}">
                <input type="hidden" name="or_date" value="{{ $batch->or_date }}">
                <input type="hidden" name="disbursed_count" value="{{ $batch->disbursed_count }}">
                <input type="hidden" name="disbursement_attachment" value="{{ $batch->disbursement_attachment }}">
                <input type="hidden" name="disbursement_scholar_file" value="{{ $batch->disbursement_scholar_file }}">
            @endif

            <div class="mt-4">
                <button type="submit" class="btn btn-icon-only btn-primary-simple" title="Save Changes">
                    <i class="fas fa-save"></i>
                </button>
                <a href="{{ route('billing.index') }}" class="btn btn-icon-only btn-outline-simple ms-2" title="Cancel">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection
