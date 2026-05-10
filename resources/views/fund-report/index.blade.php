@extends('layouts.user_type.auth')

@section('content')

<div class="row mb-4">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">Fund Report Summary</h5>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body p-3">
            <form action="{{ route('fund-report.index') }}" method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <label class="text-xs font-weight-bold">Program</label>
                    <select name="program" class="form-select form-select-sm">
                        <option value="">All Programs</option>
                        @foreach($programs as $prog)
                            @if($prog)
                                <option value="{{ $prog }}" {{ request('program') == $prog ? 'selected' : '' }}>{{ $prog }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="text-xs font-weight-bold">Semester</label>
                    <select name="semester" class="form-select form-select-sm">
                        <option value="">All Semesters</option>
                        @foreach($semesters as $sem)
                            @if($sem)
                                <option value="{{ $sem }}" {{ request('semester') == $sem ? 'selected' : '' }}>{{ $sem }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="text-xs font-weight-bold">AY</label>
                    <select name="ay" class="form-select form-select-sm">
                        <option value="">All Academic Years</option>
                        @foreach($ays as $ay)
                            @if($ay)
                                <option value="{{ $ay }}" {{ request('ay') == $ay ? 'selected' : '' }}>{{ $ay }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end mt-4">
                    <button type="submit" class="btn btn-primary-simple btn-sm btn-icon-only mb-0 me-2" title="Filter Report">
                        <i class="fas fa-filter"></i>
                    </button>
                    @if(request()->anyFilled(['program', 'semester', 'ay']))
                        <a href="{{ route('fund-report.index') }}" class="btn btn-outline-simple btn-sm btn-icon-only mb-0" title="Clear Filters">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if(request()->anyFilled(['program', 'semester', 'ay']))
    <div class="card">
        <div class="table-responsive">
            <table class="table align-items-center mb-0">
                <thead>
                    <tr>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Program / AY / Sem</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Batches</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Total Scholars</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Disbursed Scholars</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-end pe-4">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $grandTotalScholars = 0;
                        $grandTotalDisbursed = 0;
                        $grandTotalAmount = 0;
                    @endphp
                    @forelse($summary as $row)
                    @php
                        $grandTotalScholars += $row->total_scholars;
                        $grandTotalDisbursed += $row->total_disbursed_scholars;
                        $grandTotalAmount += $row->total_billing_amount;
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex px-3 py-1">
                                <div class="d-flex flex-column justify-content-center">
                                    <h6 class="mb-0 text-sm">{{ $row->program }}</h6>
                                    <p class="text-xs text-secondary mb-0">AY {{ $row->ay }} | {{ $row->semester }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="align-middle text-center text-sm">
                            <span class="font-weight-bold">{{ $row->total_batches }}</span>
                        </td>
                        <td class="align-middle text-center text-sm">
                            <span class="font-weight-bold">{{ $row->total_scholars }}</span>
                        </td>
                        <td class="align-middle text-center text-sm">
                            <span class="font-weight-bold text-info">{{ $row->total_disbursed_scholars }}</span>
                        </td>
                        <td class="align-middle text-end text-sm pe-4">
                            <span class="font-weight-bold text-primary-simple">₱{{ number_format($row->total_billing_amount, 2) }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4">No data available for the selected filters.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($summary) > 0)
                <tfoot class="bg-gray-100">
                    <tr>
                        <td class="text-sm ps-4 font-weight-bold">GRAND TOTAL</td>
                        <td class="text-center font-weight-bold"></td>
                        <td class="text-center font-weight-bold">{{ number_format($grandTotalScholars) }}</td>
                        <td class="text-center font-weight-bold text-primary-simple">{{ number_format($grandTotalDisbursed) }}</td>
                        <td class="text-end pe-4 font-weight-bold text-primary-simple">₱{{ number_format($grandTotalAmount, 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    @else
    <div class="card">
        <div class="card-body p-5 text-center">
            <div class="mb-4">
                <i class="fas fa-chart-pie text-secondary opacity-3" style="font-size: 4rem;"></i>
            </div>
            <h6 class="text-secondary">Select a Program, Semester, or Academic Year</h6>
            <p class="text-sm text-muted">Use the dropdown filters above to generate the fund report summary.</p>
        </div>
    </div>
    @endif

  </div>
</div>

@endsection
