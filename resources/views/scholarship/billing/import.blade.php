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
            <h5 class="mb-1">Bulk Billing Import</h5>
            <p class="text-sm mb-0 text-secondary">Upload CSV files to create multiple billing records at once.</p>
          </div>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
          <a href="{{ route('scholarship-billing.create') }}" class="btn btn-sm btn-outline-primary mb-0 border-radius-md">
            <i class="fas fa-user-plus me-1"></i> Manual Entry
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

@if ($errors->any() || !empty($importError ?? ''))
  <div class="row">
    <div class="col-12">
      <div class="alert alert-danger text-white border-radius-xl shadow-danger" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-circle me-3 text-lg"></i>
            <div>
                <strong class="text-sm">Import Error:</strong>
                <p class="text-xs mb-0">{{ $importError ?? 'Please correct the issues in your file.' }}</p>
                @if ($errors->any())
                <ul class="mb-0 text-xs mt-1">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
                @endif
            </div>
        </div>
      </div>
    </div>
  </div>
@endif

@if (session('success'))
  <div class="row">
    <div class="col-12">
      <div class="alert alert-success text-white border-radius-xl shadow-success" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
      </div>
    </div>
  </div>
@endif

<div class="row">
  <!-- Step 1: File Selection -->
  <div class="col-lg-12">
    <div class="card mb-4 shadow-sm border-radius-xl">
      <div class="card-header pb-0 p-3">
        <div class="d-flex align-items-center">
            <div class="icon icon-shape bg-gray-100 text-center rounded-circle me-3" style="width: 32px; height: 32px;">
                <i class="fas fa-upload text-success opacity-10 text-xs"></i>
            </div>
            <h6 class="mb-0">Step 1: Upload Billing File</h6>
        </div>
      </div>
      <div class="card-body p-3">
        <div class="row bg-gray-100 border-radius-lg p-3 mb-3 mx-1">
            <div class="col-md-6 border-end">
                <p class="text-xs font-weight-bold text-uppercase mb-2 text-secondary">Download Templates</p>
                <div class="d-flex gap-3">
                    <a href="{{ route('scholarship-billing.import.template', 'detailed') }}" class="btn btn-xs btn-white mb-0 border-radius-md shadow-none">
                        <i class="fas fa-file-csv text-info me-1"></i> Detailed (Per Student)
                    </a>
                    <a href="{{ route('scholarship-billing.import.template', 'batch') }}" class="btn btn-xs btn-white mb-0 border-radius-md shadow-none">
                        <i class="fas fa-table text-primary me-1"></i> Batch Summary
                    </a>
                </div>
            </div>
            <div class="col-md-6 ps-md-4 mt-3 mt-md-0 text-center text-md-start">
                <p class="text-xs font-weight-bold text-uppercase mb-2 text-secondary">Accepted Formats</p>
                <span class="badge badge-sm bg-gradient-info border-radius-md me-1">CSV</span>
                <span class="badge badge-sm bg-gradient-dark border-radius-md me-1">PDF / IMAGE (For Docs)</span>
            </div>
        </div>

        <form method="POST" action="{{ route('scholarship-billing.import.process') }}" enctype="multipart/form-data" class="row g-3">
          @csrf
          @php 
            $activeTempPath = $tempPath ?? old('temp_path'); 
            $activeSignedDoc = $signedDocPath ?? old('signed_doc_path');
          @endphp
          @if (!empty($activeTempPath))
            <input type="hidden" name="temp_path" value="{{ $activeTempPath }}" />
          @endif
          @if (!empty($activeSignedDoc))
            <input type="hidden" name="signed_doc_path" value="{{ $activeSignedDoc }}" />
          @endif
          <div class="col-md-5">
            <label class="form-label text-xs font-weight-bold"><i class="fas fa-file-csv text-success me-1"></i> SELECT BILLING CSV</label>
            <input type="file" name="billing_csv" class="form-control border-radius-md" accept=".csv" />
          </div>
          <div class="col-md-5">
            <label class="form-label text-xs font-weight-bold"><i class="fas fa-magic text-info me-1"></i> SMART SCAN (PDF / IMAGE)</label>
            <input type="file" name="document_scan" class="form-control border-radius-md" accept=".pdf,image/*" />
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" name="action" value="analyze" class="btn btn-info w-100 mb-0 border-radius-md shadow-none">
              <i class="fas fa-microscope me-1"></i> Scan
            </button>
          </div>

          <div class="col-md-5 mt-2">
            <label class="form-label text-xs font-weight-bold text-secondary"><i class="fas fa-signature me-1"></i> SIGNED DOCUMENT (OPTIONAL)</label>
            <input type="file" name="signed_billing_doc" class="form-control border-radius-md" />
          </div>
          <div class="col-md-5 mt-2">
            <label class="form-label text-xs font-weight-bold text-secondary"><i class="fas fa-play text-success me-1"></i> ACTION</label>
            <select name="mode" class="form-select border-radius-md">
              <option value="preview">Scan & Preview</option>
              <option value="import">Final Import</option>
            </select>
          </div>
          <div class="col-md-2 d-flex align-items-end mt-2">
            <button type="submit" name="action" value="run" class="btn btn-success w-100 mb-0 border-radius-md shadow-success">
              <i class="fas fa-cog me-1"></i> Run
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  @if (!empty($csvHeaders ?? []))
  <!-- Step 2: Mapping -->
  <div class="col-12">
    <div class="card mb-4 shadow-sm border-radius-xl border-primary">
      <div class="card-header pb-0 p-3 bg-gray-100 border-radius-xl-top">
        <div class="d-flex align-items-center">
            <div class="icon icon-shape bg-primary shadow-primary text-center rounded-circle me-3" style="width: 32px; height: 32px;">
                <i class="fas fa-exchange-alt text-white opacity-10 text-xs"></i>
            </div>
            <h6 class="mb-0">Step 2: Map Your CSV Columns</h6>
        </div>
      </div>
      <div class="card-body p-4">
        <form method="POST" action="{{ route('scholarship-billing.import.process') }}">
          @csrf
          <input type="hidden" name="temp_path" value="{{ $tempPath }}" />
          <input type="hidden" name="signed_doc_path" value="{{ $signedDocPath }}" />
          <input type="hidden" name="mode" value="import" />

          @if (!empty($isAiScan))
          <input type="hidden" name="ai_data" value="{{ json_encode($rows) }}" />
          <div class="alert alert-info text-white text-xs border-radius-lg mb-3">
             <i class="fas fa-info-circle me-2"></i> AI Data extracted! No mapping required. Just click confirm below.
          </div>
          @endif

          <div class="row g-4">
            @php
              $fields = [
                'program' => 'Scholarship Program',
                'semester' => 'Semester',
                'academic_year' => 'Academic Year',
                'submitdate' => 'Billing Date',
                'paid' => 'Amount / Peso',
                'scholar_count' => 'Scholar Count',
                'student_id' => 'Student ID No.',
                'full_name' => 'Student Full Name',
                'address' => 'Home Address',
                'contact' => 'Contact Number',
                'course' => 'Course / Degree',
                'year_level' => 'Year Level',
                'disbursed_date' => 'Disbursement Date (Optional)',
                'batch_label' => 'Batch Label',
                'region' => 'Region / Campus',
                'fb_link' => 'Facebook Link / FB account',
              ];
            @endphp

            @foreach ($fields as $key => $label)
            <div class="col-md-6 col-xl-3">
              <div class="form-group mb-0">
                <label class="form-label text-xs font-weight-bold text-uppercase">{{ $label }}</label>
                <select name="map[{{ $key }}]" class="form-select border-radius-md border-primary-soft">
                  <option value="">-- Ignore --</option>
                  @foreach ($csvHeaders as $index => $header)
                    @php
                      $isSelected = false;
                      // Priority 1: Check if this was the previously mapped value
                      if (isset($mapping[$key]) && (string)$mapping[$key] === (string)$index) {
                          $isSelected = true;
                      } 
                      // Priority 2: Check if this was auto-detected
                      elseif (isset($autoMapping[$key]) && (string)$autoMapping[$key] === (string)$index) {
                          $isSelected = true;
                      }
                    @endphp
                    <option value="{{ $index }}" {{ $isSelected ? 'selected' : '' }}>
                      {{ $header }}
                    </option>
                  @endforeach
                </select>
              </div>
            </div>
            @endforeach

            <div class="col-12 mt-4 text-center">
                <hr class="horizontal dark">
                <button type="submit" name="action" value="run" class="btn bg-gradient-primary border-radius-xl px-5">
                    <i class="fas fa-check-double me-2"></i> Confirm and Complete Import
                </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  @endif

  @if (!empty($rows))
  <!-- Preview Section -->
  <div class="col-12">
    <div class="card mb-4 shadow-sm border-radius-xl">
      <div class="card-header pb-0 p-3 bg-gray-100 border-radius-xl-top d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <div class="icon icon-shape bg-gray-200 text-center rounded-circle me-3" style="width: 32px; height: 32px;">
                <i class="fas fa-list text-dark opacity-10 text-xs"></i>
            </div>
            <h6 class="mb-0">Data Preview</h6>
        </div>
        <div class="d-flex gap-2 align-items-center">
            @if (($selectedMode ?? '') === 'preview')
            <form method="POST" action="{{ route('scholarship-billing.import.process') }}" class="me-2">
                @csrf
                <input type="hidden" name="temp_path" value="{{ $activeTempPath ?? '' }}" />
                <input type="hidden" name="mode" value="import" />
                <input type="hidden" name="action" value="run" />
                @if (!empty($mapping))
                  @foreach ($mapping as $mk => $mv)
                    <input type="hidden" name="map[{{ $mk }}]" value="{{ $mv }}" />
                  @endforeach
                @endif
                <input type="hidden" name="action" value="run" />
                <input type="hidden" name="mode" value="import" />
                <button type="submit" class="btn btn-sm bg-gradient-primary mb-0 border-radius-md">
                    <i class="fas fa-check-circle me-1"></i> Finalize & Complete Import
                </button>
            </form>
            @endif
            <span class="badge badge-sm bg-gradient-success">Valid: {{ $summary['valid'] }}</span>
            <span class="badge badge-sm bg-gradient-danger">Invalid: {{ $summary['invalid'] }}</span>
        </div>
      </div>
      <div class="card-body px-0 pt-0 pb-2">
        <div class="table-responsive p-0">
          <table class="table align-items-center mb-0">
            <thead>
              <tr class="bg-gray-100">
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Student / Info</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Program / Sem</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Date</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-end pe-4">Amount</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($rows as $row)
              <tr>
                <td class="ps-4">
                  <div class="d-flex flex-column">
                    <h6 class="mb-0 text-sm">{{ $row['full_name'] ?? ($row['student_id'] ?? 'Group/Batch') }}</h6>
                    <p class="text-xs text-secondary mb-0">
                        @if(!empty($row['fb_link']) && strtolower($row['fb_link']) !== 'n/a' && strtolower($row['fb_link']) !== 'none')
                            <span class="badge badge-sm bg-gradient-info"><i class="fab fa-facebook me-1"></i> FB Linked</span>
                        @endif
                    </p>
                  </div>
                </td>
                <td>
                  <div class="d-flex flex-column">
                    <h6 class="mb-0 text-sm">{{ $row['program'] ?? '-' }}</h6>
                    <p class="text-xs text-secondary mb-0">{{ $row['semester'] ?? '-' }} ({{ $row['academic_year'] ?? '-' }})</p>
                  </div>
                </td>
                <td>
                  <p class="text-xs font-weight-bold mb-0">{{ $row['submitdate'] ?? '-' }}</p>
                </td>
                <td class="text-end pe-4">
                  <p class="text-sm font-weight-bold mb-0 text-dark">₱{{ number_format($row['paid'] ?? 0, 2) }}</p>
                  <p class="text-xxs text-secondary mb-0">{{ $row['scholar_count'] ?? 0 }} Scholars</p>
                </td>
                <td class="align-middle text-center">
                  @if ($row['is_valid'])
                    @php
                      $conflictNote = (string) ($row['conflict_note'] ?? '');
                      $isPriorYear = stripos($conflictNote, 'prior year') !== false;
                      $isUpdateOnly = !empty($row['is_profile_update_only']);
                    @endphp
                    @if ($isUpdateOnly)
                      <span class="badge badge-sm bg-gradient-info">UPDATE PROFILE</span>
                    @elseif ($isPriorYear)
                      <span class="badge badge-sm bg-gradient-info" title="{{ $conflictNote }}">PRIOR YEAR</span>
                    @else
                      <span class="badge badge-sm bg-gradient-success">READY</span>
                    @endif
                  @else
                    <span class="badge badge-sm bg-gradient-danger" title="{{ implode(', ', $row['errors'] ?? []) }}">INVALID</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>

@endsection
