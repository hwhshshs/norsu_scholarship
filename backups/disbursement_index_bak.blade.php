@extends('layouts.user_type.auth')

@section('content')

<div class="row mb-4">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">Disbursement Records</h5>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body p-3">
            <form action="{{ route('disbursement.index') }}" method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <label class="text-xs font-weight-bold">Program</label>
                    <input type="text" name="program" class="form-control form-control-sm" value="{{ request('program') }}" placeholder="e.g. TDP-TES">
                </div>
                <div class="col-md-3">
                    <label class="text-xs font-weight-bold">Semester</label>
                    <input type="text" name="semester" class="form-control form-control-sm" value="{{ request('semester') }}" placeholder="e.g. 1st Semester">
                </div>
                <div class="col-md-3">
                    <label class="text-xs font-weight-bold">AY</label>
                    <input type="text" name="ay" class="form-control form-control-sm" value="{{ request('ay') }}" placeholder="e.g. 2025-2026">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-dark btn-sm mb-0">Filter</button>
                    @if(request()->anyFilled(['program', 'semester', 'ay']))
                        <a href="{{ route('disbursement.index') }}" class="btn btn-light btn-sm mb-0 ms-2">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @forelse($batches as $batch)
    <div class="card mb-4 shadow-sm border border-light">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <tbody>
                        <tr>
                            <th class="w-25 bg-gray-100 text-xs font-weight-bolder text-uppercase text-secondary align-middle">Program</th>
                            <td class="text-sm font-weight-bold text-dark">{{ $batch->program }}</td>
                        </tr>
                        <tr>
                            <th class="w-25 bg-gray-100 text-xs font-weight-bolder text-uppercase text-secondary align-middle">Batch</th>
                            <td class="text-sm font-weight-bold text-dark">{{ $batch->batch ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="w-25 bg-gray-100 text-xs font-weight-bolder text-uppercase text-secondary align-middle">AY</th>
                            <td class="text-sm font-weight-bold text-dark">{{ $batch->ay }}</td>
                        </tr>
                        <tr>
                            <th class="w-25 bg-gray-100 text-xs font-weight-bolder text-uppercase text-secondary align-middle">Semester</th>
                            <td class="text-sm font-weight-bold text-dark">{{ $batch->semester }}</td>
                        </tr>

                        <tr>
                            <th class="w-25 bg-gray-100 text-xs font-weight-bolder text-uppercase text-secondary align-middle">Region</th>
                            <td class="text-sm font-weight-bold text-dark">{{ $batch->region ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="w-25 bg-gray-100 text-xs font-weight-bolder text-uppercase text-secondary align-middle">No Scholars (View the number of Scholars)</th>
                            <td class="text-sm font-weight-bold text-dark">
                                <a href="{{ route('disbursement.show', $batch->id) }}" class="text-info text-decoration-underline">{{ $batch->scholar_count }}</a>
                            </td>
                        </tr>
                        <tr>
                            <th class="w-25 bg-gray-100 text-xs font-weight-bolder text-uppercase text-secondary align-middle">Date of Billing</th>
                            <td class="text-sm font-weight-bold text-dark">{{ $batch->billing_date ? \Carbon\Carbon::parse($batch->billing_date)->format('M d, Y') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="w-25 bg-gray-100 text-xs font-weight-bolder text-uppercase text-secondary align-middle">Date on ADA Details</th>
                            <td class="text-sm font-weight-bold text-dark">{{ $batch->ada_date ? \Carbon\Carbon::parse($batch->ada_date)->format('M d, Y') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="w-25 bg-gray-100 text-xs font-weight-bolder text-uppercase text-secondary align-middle">ADA No.</th>
                            <td class="text-sm font-weight-bold text-dark">{{ $batch->ada_no ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="w-25 bg-gray-100 text-xs font-weight-bolder text-uppercase text-secondary align-middle">Amount</th>
                            <td class="text-sm font-weight-bold text-success">₱{{ number_format($batch->amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th class="w-25 bg-gray-100 text-xs font-weight-bolder text-uppercase text-secondary align-middle">Admin Cost</th>
                            <td class="text-sm font-weight-bold text-dark">₱{{ number_format($batch->admin_cost, 2) }}</td>
                        </tr>
                        <tr>
                            <th class="w-25 bg-gray-100 text-xs font-weight-bolder text-uppercase text-secondary align-middle">OR Number</th>
                            <td class="text-sm font-weight-bold text-dark">{{ $batch->or_number ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="w-25 bg-gray-100 text-xs font-weight-bolder text-uppercase text-secondary align-middle">OR Date</th>
                            <td class="text-sm font-weight-bold text-dark">{{ $batch->or_date ? \Carbon\Carbon::parse($batch->or_date)->format('M d, Y') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="w-25 bg-gray-100 text-xs font-weight-bolder text-uppercase text-secondary align-middle">Status (No of Students Disburse)</th>
                            <td class="text-sm font-weight-bold text-info">{{ $batch->disbursed_count }} / {{ $batch->scholar_count }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="card-body text-center py-5">
            <p class="text-secondary mb-0">No disbursement records found.</p>
        </div>
    </div>
    @endforelse

    <div class="mt-4">
        {{ $batches->links() }}
    </div>

  </div>
</div>

@endsection
