@extends('layouts.user_type.auth')

@section('content')

<div class="row mb-4">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">Billing Batches</h5>
        <a href="{{ route('billing.create') }}" class="btn btn-primary mb-0"><i class="fas fa-plus me-2"></i>New Billing</a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body p-3">
            <form action="{{ route('billing.index') }}" method="GET" class="row align-items-end">
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
                        <a href="{{ route('billing.index') }}" class="btn btn-light btn-sm mb-0 ms-2">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @forelse($batches as $batch)
    <div class="card mb-3 shadow-sm border border-light overflow-hidden hover-shadow">
        <!-- Compact Header -->
        <div class="card-header p-3 bg-white cursor-pointer" 
             id="heading{{ $batch->id }}" 
             data-bs-toggle="collapse" 
             data-bs-target="#collapse{{ $batch->id }}" 
             aria-expanded="false" 
             style="cursor: pointer; border-bottom: 0;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center flex-wrap">
                    <span class="badge bg-gradient-primary me-2 px-3">{{ $batch->program }}</span>
                    <span class="text-sm font-weight-bold text-dark me-3">{{ $batch->batch ?? 'N/A' }}</span>
                    <div class="vr mx-2 d-none d-sm-block" style="height: 20px; opacity: 0.2;"></div>
                    <span class="text-xs font-weight-bold text-secondary text-uppercase me-3 d-flex align-items-center">
                        <i class="fas fa-users me-1 text-info"></i> {{ $batch->scholar_count }} Scholars
                    </span>
                    <div class="vr mx-2 d-none d-sm-block" style="height: 20px; opacity: 0.2;"></div>
                    <span class="text-sm font-weight-bold text-success">
                        ₱{{ number_format($batch->amount, 2) }}
                    </span>
                </div>
                <div class="d-flex align-items-center">
                    <span class="text-xs text-secondary me-3 d-none d-md-block">{{ $batch->ay }}</span>
                    <i class="fas fa-chevron-down text-xs text-secondary transition-all opacity-50"></i>
                </div>
            </div>
        </div>

        <!-- Collapsible Body (Vertical Table) -->
        <div id="collapse{{ $batch->id }}" class="collapse" aria-labelledby="heading{{ $batch->id }}">
            <div class="card-body p-0 border-top bg-gray-100">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 bg-white">
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
                                <th class="w-25 bg-gray-100 text-xs font-weight-bolder text-uppercase text-secondary align-middle">No Scholars (upload list of scholars)</th>
                                <td class="text-sm font-weight-bold text-dark">
                                    <a href="{{ route('billing.show', $batch->id) }}" class="text-info text-decoration-underline">{{ $batch->scholar_count }}</a>
                                </td>
                            </tr>
                            <tr>
                                <th class="w-25 bg-gray-100 text-xs font-weight-bolder text-uppercase text-secondary align-middle">Date of Billing</th>
                                <td class="text-sm font-weight-bold text-dark">{{ $batch->billing_date ? \Carbon\Carbon::parse($batch->billing_date)->format('M d, Y') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="w-25 bg-gray-100 text-xs font-weight-bolder text-uppercase text-secondary align-middle">Amount</th>
                                <td class="text-sm font-weight-bold text-success">₱{{ number_format($batch->amount, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-3 bg-white text-end border-top">
                    <a href="{{ route('billing.edit', $batch->id) }}" class="btn btn-outline-primary btn-sm mb-0">
                        <i class="fas fa-edit me-1"></i> Edit Batch
                    </a>
                    <a href="{{ route('billing.show', $batch->id) }}" class="btn btn-info btn-sm mb-0 ms-2">
                        <i class="fas fa-eye me-1"></i> Full Details
                    </a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="card-body text-center py-5">
            <p class="text-secondary mb-0">No billing records found.</p>
        </div>
    </div>
    @endforelse

    <div class="mt-4">
        {{ $batches->links() }}
    </div>

  </div>
</div>

@endsection
