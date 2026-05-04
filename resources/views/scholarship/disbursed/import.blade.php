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
            <h5 class="mb-1">Bulk Disbursement Import</h5>
            <p class="text-sm mb-0 text-secondary">Upload CSV files to finalize payments for multiple students at once.</p>
          </div>
        </div>
        <div class="mt-3 mt-md-0 d-flex flex-wrap gap-2">
          <a href="{{ route('scholarship-disbursed.entry.form') }}" class="btn btn-sm btn-outline-primary mb-0 border-radius-md">
            <i class="fas fa-user-plus me-1"></i> Manual Entry
          </a>
          <a href="{{ route('scholarship-system.module', 'disbursed-import') }}" class="btn btn-outline-secondary mb-0">Legacy Page</a>
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

@if (!empty($importError))
  <div class="row">
    <div class="col-12">
      <div class="alert alert-danger text-white" role="alert">{{ $importError }}</div>
    </div>
  </div>
@endif

<div class="row">
  <div class="col-lg-9 col-12">
    <div class="card mb-4">
      <div class="card-header pb-0">
        <h6 class="mb-0">Import File</h6>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('scholarship-disbursed.import.process') }}" enctype="multipart/form-data" class="row g-3">
          @csrf

          <div class="col-md-6">
            <label class="form-label">Billing Batch</label>
            @php
              $oldBatchId = (int) old('billing_batch_id', $selectedBatchId);
            @endphp
            <select name="billing_batch_id" class="form-control" required>
              <option value="">Select Batch</option>
              @foreach ($batchOptions as $batch)
                <option value="{{ $batch->id }}" {{ $oldBatchId === (int) $batch->id ? 'selected' : '' }}>
                  {{ $batch->program_batch_ref ?: ('#' . $batch->id) }} | {{ $batch->program }} | {{ $batch->academic_year }} | {{ $batch->semester }} | {{ $batch->batch_label }} | {{ $batch->region }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Default Disbursed Date</label>
            <input type="date" name="disbursed_date" class="form-control" value="{{ old('disbursed_date', date('Y-m-d')) }}" required />
          </div>

          <div class="col-md-3">
            <label class="form-label">Mode</label>
            @php
              $oldMode = old('mode', $selectedMode);
            @endphp
            <select name="mode" class="form-control">
              <option value="preview" {{ $oldMode === 'preview' ? 'selected' : '' }}>Preview</option>
              <option value="import" {{ $oldMode === 'import' ? 'selected' : '' }}>Import</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Disbursed CSV</label>
            <input type="file" name="disbursed_csv" class="form-control" accept=".csv,.txt" required />
            <small class="text-xs text-secondary">Required headers: student_id and disbursed_amount (or amount_received). Optional: name, course, disbursed_date, remarks, ada_no, or_no, or_date.</small>
            <small class="text-xs text-secondary d-block">Template: <a href="{{ route('scholarship-disbursed.import.template') }}">Download CSV template</a>.</small>
          </div>

          <div class="col-md-6 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input check-icon-input" type="checkbox" id="syncBillingImport" name="sync_billing" value="1" {{ old('sync_billing', '1') === '1' ? 'checked' : '' }}>
              <label class="form-check-label check-icon-label" for="syncBillingImport">Create disbursed log rows in fees_transaction</label>
            </div>
          </div>

          <div class="col-md-3">
            <label class="form-label">ADA No.</label>
            <input type="text" name="ada_no" class="form-control" value="{{ old('ada_no') }}" />
          </div>

          <div class="col-md-3">
            <label class="form-label">Date on ADA</label>
            <input type="date" name="date_on_ada_details" class="form-control" value="{{ old('date_on_ada_details') }}" />
          </div>

          <div class="col-md-3">
            <label class="form-label">OR No.</label>
            <input type="text" name="or_no" class="form-control" value="{{ old('or_no') }}" />
          </div>

          <div class="col-md-3">
            <label class="form-label">OR Date</label>
            <input type="date" name="or_date" class="form-control" value="{{ old('or_date') }}" />
          </div>

          <div class="col-md-3">
            <label class="form-label">Admin Cost</label>
            <input type="number" step="0.01" min="0" name="admin_cost" class="form-control" value="{{ old('admin_cost') }}" />
          </div>

          <div class="col-md-6">
            <label class="form-label">Attachment Note</label>
            <input type="text" name="attachment_note" class="form-control" value="{{ old('attachment_note') }}" maxlength="255" />
          </div>

          <div class="col-md-6">
            <label class="form-label">Attachment File</label>
            <input type="file" name="attachment_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.csv,.txt,.xlsx,.xls" />
            <small class="text-xs text-secondary">No template needed for attachment uploads.</small>
          </div>

          <div class="col-12">
            <label class="form-label">Remarks</label>
            <input type="text" name="remarks" class="form-control" value="{{ old('remarks') }}" maxlength="255" />
          </div>

          <div class="col-12 d-flex flex-wrap">
            <button type="submit" class="btn bg-gradient-primary mb-0 me-2">Process File</button>
            <a href="{{ route('scholarship-disbursed.import.template') }}" class="btn btn-outline-dark mb-0">Download CSV Template</a>
          </div>
        </form>
      </div>
    </div>

    @php
      $summaryData = is_array($summary ?? null) ? $summary : [];
      $totalRows = (int) ($summaryData['total'] ?? 0);
      $validRows = (int) ($summaryData['valid'] ?? 0);
      $invalidRows = (int) ($summaryData['invalid'] ?? 0);
      $totalAmount = (float) ($summaryData['total_amount'] ?? 0);
      $importedRows = (int) ($summaryData['imported'] ?? 0);
      $rowList = is_iterable($rows ?? null) ? $rows : [];
    @endphp

    @if ($totalRows > 0)
      <div class="card">
        <div class="card-header pb-0">
          <h6 class="mb-0">Preview Results</h6>
        </div>
        <div class="card-body pt-3 pb-2">
          <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="border rounded p-2 text-sm">Total Rows: <strong>{{ number_format($totalRows) }}</strong></div></div>
            <div class="col-md-3"><div class="border rounded p-2 text-sm">Valid Rows: <strong>{{ number_format($validRows) }}</strong></div></div>
            <div class="col-md-3"><div class="border rounded p-2 text-sm">Invalid Rows: <strong>{{ number_format($invalidRows) }}</strong></div></div>
            <div class="col-md-3"><div class="border rounded p-2 text-sm">Total Amount: <strong>{{ number_format($totalAmount, 2) }}</strong></div></div>
          </div>

          @if ($importedRows > 0)
            <div class="alert alert-success text-white" role="alert">Imported rows: {{ number_format($importedRows) }}</div>
          @endif

          <div class="table-responsive">
            <table class="table align-items-center mb-0">
              <thead>
                <tr>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Line</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Student</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Profile Status</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Amount</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Date</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Message</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($rowList as $row)
                  @php
                    $isValid = !empty($row['is_valid']);
                    $rowErrors = is_array($row['errors'] ?? null) ? $row['errors'] : [];
                  @endphp
                  <tr>
                    <td class="text-sm">{{ (int) ($row['line'] ?? 0) }}</td>
                    <td class="text-sm">{{ $row['student_id'] ?? '' }}</td>
                    <td class="text-sm">
                      @php
                        $isComplete = !empty($row['is_profile_complete']);
                        $missing = is_array($row['missing_fields'] ?? null) ? $row['missing_fields'] : [];
                        $percent = (int) ($row['completion_percentage'] ?? 0);
                        $fullName = $row['full_name'] ?? ('Student ID: ' . ($row['student_id'] ?? 'N/A'));
                      @endphp
                      @if ($isComplete)
                        <span class="badge bg-gradient-success" title="Profile Complete">🟢 Complete</span>
                      @else
                        <span class="badge bg-gradient-warning text-dark" style="cursor:help;" 
                              title="Incomplete: {{ implode(', ', $missing) }} ({{ $percent }}%)">
                          ⚠️ Incomplete
                        </span>
                      @endif
                    </td>
                    <td class="text-sm">{{ number_format((float) ($row['amount'] ?? 0), 2) }}</td>
                    <td class="text-sm">{{ $row['disbursed_date'] ?? '' }}</td>
                    <td>
                      <span class="badge {{ $isValid ? 'bg-gradient-success' : 'bg-gradient-danger' }}">{{ $isValid ? 'VALID' : 'INVALID' }}</span>
                    </td>
                    <td class="text-sm">{{ $isValid ? 'Ready for import' : implode(', ', $rowErrors) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @endif
  </div>

  <div class="col-lg-3 col-12">
    <div class="card mb-4">
      <div class="card-header pb-0">
        <h6 class="mb-0">Import Notes</h6>
      </div>
      <div class="card-body">
        <ul class="text-sm mb-0 ps-3">
          <li>Use Preview first to catch row-level issues.</li>
          <li>All imported students must exist in selected batch draft rows.</li>
          <li>Optional CSV context columns are validated when provided.</li>
          <li>Completed/archived batches are blocked from import.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

@endsection
