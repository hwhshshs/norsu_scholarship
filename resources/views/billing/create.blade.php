@extends('layouts.user_type.auth')

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header pb-0 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-0">Create New Billing Batch</h6>
            <p class="text-sm mb-0">Enter all batch details and upload the list of scholars.</p>
        </div>
        <a href="{{ url()->previous() }}" class="btn btn-icon-only btn-sm btn-outline-simple mb-0" title="Return">
            <i class="fas fa-arrow-left"></i>
        </a>
      </div>
      <div class="card-body">
        <form action="{{ route('billing.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3 mt-2">Billing Information</h6>
            <div class="row bg-gray-100 p-3 rounded mb-4">
                <div class="col-12 mb-3">
                    <label class="form-label">Program *</label>
                    <select name="program" class="form-control" required>
                        <option value="">Select Program</option>
                        <option value="TDP-TES" {{ old('program') == 'TDP-TES' ? 'selected' : '' }}>TDP-TES</option>
                        <option value="CHED" {{ old('program') == 'CHED' ? 'selected' : '' }}>CHED</option>
                        <option value="ACEF-GIAHEP" {{ old('program') == 'ACEF-GIAHEP' ? 'selected' : '' }}>ACEF-GIAHEP</option>
                        <option value="CMSP" {{ old('program') == 'CMSP' ? 'selected' : '' }}>CMSP</option>
                    </select>
                    @error('program') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Semester *</label>
                    <select name="semester" class="form-control" required>
                        <option value="">Select Semester</option>
                        <option value="1st Semester" {{ old('semester') == '1st Semester' ? 'selected' : '' }}>1st Semester</option>
                        <option value="2nd Semester" {{ old('semester') == '2nd Semester' ? 'selected' : '' }}>2nd Semester</option>
                        <option value="Summer/Midyear" {{ old('semester') == 'Summer/Midyear' ? 'selected' : '' }}>Summer/Midyear</option>
                    </select>
                    @error('semester') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">AY *</label>
                    <select name="ay" class="form-control" required>
                        <option value="">Select Academic Year</option>
                        @php
                            $startYear = 2021;
                            $endYear = 2028;
                        @endphp
                        @for ($i = $startYear; $i < $endYear; $i++)
                            @php $ay = "$i-" . ($i + 1); @endphp
                            <option value="{{ $ay }}" {{ old('ay') == $ay ? 'selected' : '' }}>{{ $ay }}</option>
                        @endfor
                    </select>
                    @error('ay') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label">Batch</label>
                    <input type="text" name="batch" class="form-control" value="{{ old('batch') }}">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Region</label>
                    <input type="text" name="region" class="form-control" value="{{ old('region') }}">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Date of Billing</label>
                    <input type="date" name="billing_date" class="form-control" value="{{ old('billing_date') }}">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Official Signed PDF Attachment</label>
                    <input type="file" name="pdf_attachment" class="form-control" accept=".pdf">
                    <small class="text-muted">For official records (signed PDF document)</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Scholar List (CSV or PDF) *</label>
                    <input type="file" name="scholar_file" class="form-control" accept=".csv,.txt,.pdf" required>
                    <small class="text-muted">Upload CSV for extraction or PDF for attachment (no extraction)</small>
                </div>
            </div>

            <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3">Disbursement Details (Optional at this stage)</h6>
            <div class="row bg-gray-100 p-3 rounded mb-4">
                <div class="col-12 mb-3">
                    <label class="form-label">Date on ADA Details</label>
                    <input type="date" name="ada_date" class="form-control" value="{{ old('ada_date') }}">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">ADA No.</label>
                    <input type="text" name="ada_no" class="form-control" value="{{ old('ada_no') }}">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">OR Number</label>
                    <input type="text" name="or_number" class="form-control" value="{{ old('or_number') }}">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">OR Date</label>
                    <input type="date" name="or_date" class="form-control" value="{{ old('or_date') }}">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Status (No. of Students Disbursed)</label>
                    <input type="number" name="disbursed_count" class="form-control" value="{{ old('disbursed_count') }}">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Official Signed PDF Attachment</label>
                        <input type="file" name="disbursement_attachment" class="form-control" accept=".pdf">
                        <small class="text-muted">For official records (signed PDF document)</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Scholar List (CSV or PDF) *</label>
                        <input type="file" name="disbursement_scholar_file" class="form-control" accept=".csv,.txt,.pdf">
                        <small class="text-muted">Upload CSV for extraction or PDF for attachment (no extraction)</small>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-icon-only btn-primary-simple" title="Save Batch">
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
