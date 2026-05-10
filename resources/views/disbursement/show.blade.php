@extends('layouts.user_type.auth')

@section('content')



<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header pb-0 d-flex justify-content-between align-items-center">
        <h6>Disbursement Details</h6>
        <div class="d-flex align-items-center">
            <a href="{{ route('disbursement.export-csv', $batch->id) }}" class="btn btn-sm btn-outline-success mb-0 me-2">
                <i class="fas fa-file-excel me-1"></i> Excel
            </a>
            <a href="{{ route('disbursement.print-report', $batch->id) }}" target="_blank" class="btn btn-sm btn-outline-danger mb-0 me-2">
                <i class="fas fa-file-pdf me-1"></i> Print PDF
            </a>
            <a href="{{ url()->previous() }}" class="btn btn-icon-only btn-sm btn-outline-simple mb-0" title="Return">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
      </div>
      <div class="card-body">
        <div class="row">
            <div class="col-12 mb-4">
                <ul class="list-group">
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Program:</strong> {{ $batch->program }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Semester:</strong> {{ $batch->semester }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">AY:</strong> {{ $batch->ay }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Batch:</strong> {{ $batch->batch ?? 'N/A' }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Region:</strong> {{ $batch->region ?? 'N/A' }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Date of Billing:</strong> {{ $batch->billing_date ? \Carbon\Carbon::parse($batch->billing_date)->format('M d, Y') : 'N/A' }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Amount:</strong> ₱{{ number_format($batch->amount, 2) }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Date on ADA:</strong> {{ $batch->ada_date ? \Carbon\Carbon::parse($batch->ada_date)->format('M d, Y') : 'N/A' }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">ADA No:</strong> {{ $batch->ada_no ?? 'N/A' }}</li>

                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">OR Number:</strong> {{ $batch->or_number ?? 'N/A' }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">OR Date:</strong> {{ $batch->or_date ? \Carbon\Carbon::parse($batch->or_date)->format('M d, Y') : 'N/A' }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Status (Disbursed):</strong> <span class="badge bg-gradient-info">{{ $batch->disbursed_count }} / {{ $batch->scholar_count }}</span></li>
                  <li class="list-group-item border-0 ps-0 text-sm">
                    <strong class="text-dark">Official Billing PDF:</strong> 
                    @if($batch->pdf_attachment)
                        <a href="{{ asset('storage/' . $batch->pdf_attachment) }}" target="_blank" class="text-primary font-weight-bold">
                            <i class="fas fa-file-pdf me-1"></i> View Signed Billing PDF
                        </a>
                    @else
                        <span class="text-secondary">None</span>
                    @endif
                  </li>
                  <li class="list-group-item border-0 ps-0 text-sm">
                    <strong class="text-dark">Disbursement PDF:</strong> 
                    @if($batch->disbursement_attachment)
                        <a href="{{ asset('storage/' . $batch->disbursement_attachment) }}" target="_blank" class="text-success font-weight-bold">
                            <i class="fas fa-file-pdf me-1"></i> View Disbursement PDF
                        </a>
                    @else
                        <span class="text-secondary">None</span>
                    @endif
                  </li>
                  <li class="list-group-item border-0 ps-0 text-sm">
                    <strong class="text-dark">Disbursement Attachment:</strong> 
                    @if($batch->disbursement_scholar_file)
                        <a href="{{ asset('storage/' . $batch->disbursement_scholar_file) }}" target="_blank" class="text-info font-weight-bold">
                            <i class="fas fa-ellipsis-v me-1"></i><i class="fas fa-chevron-right me-1"></i> View Attachment
                        </a>
                    @else
                        <span class="text-secondary">None</span>
                    @endif
                  </li>
                </ul>
            </div>
        </div>

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
                            <p class="text-sm font-weight-bold mb-0 text-primary">
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

@endsection
