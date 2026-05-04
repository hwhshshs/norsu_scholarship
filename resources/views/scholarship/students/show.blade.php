
@extends('layouts.user_type.auth')

@section('content')

@php
    $isInactive = (string) ($student->delete_status ?? '0') === '1';
    $displayName = trim(($student->last_name ?? '') . ', ' . ($student->given_name ?? ''));
    if (trim($displayName, ', ') === '') {
        $displayName = $student->sname ?: 'Unnamed Student';
    }
@endphp

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-body d-md-flex align-items-center justify-content-between p-4">
                <div class="d-flex align-items-center">
                    <div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle me-3">
                        <i class="fas fa-user-graduate text-white opacity-10"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">{{ $displayName }}</h5>
                        <p class="text-sm mb-0">Student ID: <strong>{{ $student->student_id_no }}</strong> | Status: <span class="badge {{ $isInactive ? 'bg-gradient-danger' : 'bg-gradient-success' }} ms-1">{{ $isInactive ? 'Inactive' : 'Active' }}</span></p>
                    </div>
                </div>
                <div class="mt-3 mt-md-0">
                    <a href="{{ url()->previous() }}" class="btn btn-outline-dark mb-0 me-2">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                    <a href="{{ route('scholarship-students.edit', $student->id) }}" class="btn bg-gradient-primary mb-0">
                        <i class="fas fa-edit me-1"></i> Manage Student
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="row">
            <!-- Academic Information -->
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header pb-0 p-3">
                        <h6 class="mb-0">Academic Profile</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 border-radius-lg bg-gray-100 h-100 border">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-award text-primary me-2"></i>
                                        <span class="text-xs text-uppercase font-weight-bold text-dark">Scholarship Program</span>
                                    </div>
                                    <p class="text-sm font-weight-bold mb-0 text-primary">{{ $student->scholarship_program ?: 'Not assigned' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border-radius-lg bg-gray-100 h-100 border">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-graduation-cap text-primary me-2"></i>
                                        <span class="text-xs text-uppercase font-weight-bold text-dark">Degree Program</span>
                                    </div>
                                    <p class="text-sm font-weight-bold mb-0">{{ $student->degree_program ?: 'Not assigned' }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border-radius-lg bg-gray-100 border">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-layer-group text-secondary me-2"></i>
                                        <span class="text-xs text-uppercase font-weight-bold text-muted">Year Level</span>
                                    </div>
                                    <p class="text-sm font-weight-bold mb-0">{{ $student->year_level ?: 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border-radius-lg bg-gray-100 border">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-calendar-alt text-secondary me-2"></i>
                                        <span class="text-xs text-uppercase font-weight-bold text-muted">Academic Year</span>
                                    </div>
                                    <p class="text-sm font-weight-bold mb-0">{{ $student->scholarship_academic_year ?: 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border-radius-lg bg-gray-100 border">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-clock text-secondary me-2"></i>
                                        <span class="text-xs text-uppercase font-weight-bold text-muted">Semester</span>
                                    </div>
                                    <p class="text-sm font-weight-bold mb-0">{{ $student->scholarship_semester ?: 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header pb-0 p-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Financial History (Billing)</h6>
                        <span class="badge bg-light text-dark">{{ $billingLedger['rows'] }} Records</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Date</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">AY / Term</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-end">Amount</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($billingLedger['history'] as $bill)
                                    <tr>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="text-xxs text-muted text-uppercase font-weight-bold">Billed</span>
                                                <p class="text-xs font-weight-bold mb-1">{{ $bill->submitdate ? \Illuminate\Support\Carbon::parse($bill->submitdate)->format('M d, Y') : '-' }}</p>
                                                
                                                @if($bill->finalized_date)
                                                    <span class="text-xxs text-success text-uppercase font-weight-bold mt-1">Paid</span>
                                                    <p class="text-xs font-weight-bold mb-0 text-success">{{ \Illuminate\Support\Carbon::parse($bill->finalized_date)->format('M d, Y') }}</p>
                                                @endif
                                            </div>
                                        </td>
                                        <td><p class="text-xs mb-0">{{ $bill->academic_year }} / {{ $bill->semester }}</p></td>
                                        <td class="text-end"><p class="text-xs font-weight-bold mb-0">₱{{ number_format($bill->paid, 2) }}</p></td>
                                        <td class="align-middle">
                                            @if($bill->conflict_status === 'scholarship_conflict')
                                                <span class="badge badge-sm bg-gradient-danger">Conflict</span>
                                            @elseif($bill->disbursed_status === 'finalized')
                                                <span class="badge badge-sm bg-gradient-info">Completed</span>
                                            @else
                                                <span class="badge badge-sm bg-gradient-success">Cleared</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-xs text-secondary">No billing history found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Personal Contact -->
        <div class="card mb-4">
            <div class="card-header pb-0 p-3">
                <h6 class="mb-0">Personal Dossier</h6>
            </div>
            <div class="card-body p-3">
                <ul class="list-group">
                    <li class="list-group-item border-0 ps-0 text-sm">
                        <strong class="text-dark">Email:</strong> &nbsp; {{ $student->emailid ?: 'Not provided' }}
                    </li>
                    <li class="list-group-item border-0 ps-0 text-sm">
                        <strong class="text-dark">Mobile:</strong> &nbsp; {{ $student->contact ?: 'Not provided' }}
                    </li>
                    <li class="list-group-item border-0 ps-0 text-sm">
                        <strong class="text-dark">Facebook:</strong> &nbsp; 
                        @if($student->fb_link)
                            <a href="{{ $student->fb_link }}" target="_blank" class="text-primary">View Profile <i class="fas fa-external-link-alt text-xs ms-1"></i></a>
                        @else
                            None
                        @endif
                    </li>
                    <li class="list-group-item border-0 ps-0 text-sm">
                        <strong class="text-dark">Address:</strong> &nbsp; {{ $student->address ?: 'Not provided' }}
                    </li>
                </ul>
            </div>
        </div>

        <!-- Special Classifications -->
        <div class="card mb-4">
            <div class="card-header pb-0 p-3">
                <h6 class="mb-0">Classifications</h6>
            </div>
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar avatar-sm bg-gradient-info shadow-info text-center rounded-circle me-3">
                        <i class="fas fa-wheelchair text-white opacity-10"></i>
                    </div>
                    <div>
                        <p class="text-xs mb-0 text-uppercase font-weight-bold">PWD ID</p>
                        <h6 class="mb-0">{{ $student->pwd_no ?: 'N/A' }}</h6>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-0">
                    <div class="avatar avatar-sm bg-gradient-dark shadow-dark text-center rounded-circle me-3">
                        <i class="fas fa-fingerprint text-white opacity-10"></i>
                    </div>
                    <div>
                        <p class="text-xs mb-0 text-uppercase font-weight-bold">IP Classification</p>
                        <h6 class="mb-0">{{ $student->ip_no ?: 'N/A' }}</h6>
                    </div>
                </div>
            </div>
        </div>

        <!-- Guardian Info -->
        <div class="card mb-4">
            <div class="card-header pb-0 p-3">
                <h6 class="mb-0">Emergency Contact</h6>
            </div>
            <div class="card-body p-3">
                <p class="text-sm mb-1"><strong class="text-dark">Guardian:</strong> {{ $student->guardian_name ?: '-' }}</p>
                <p class="text-sm mb-0"><strong class="text-dark">Contact:</strong> {{ $student->guardian_contact ?: '-' }}</p>
            </div>
        </div>
    </div>
</div>

@endsection
