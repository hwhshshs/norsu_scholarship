@extends('layouts.user_type.auth')

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header pb-0 d-flex justify-content-between align-items-center">
        <h6>Student Information</h6>
        <div>
            <a href="{{ route('student-info.edit', $student->id) }}" class="btn btn-sm btn-primary mb-0">Edit</a>
            <a href="{{ route('student-info.index') }}" class="btn btn-sm btn-secondary mb-0">Back</a>
        </div>
      </div>
      <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-4">
                <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3">Personal Info</h6>
                <ul class="list-group">
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Name:</strong> {{ $student->last_name }}, {{ $student->given_name }} {{ $student->middle_initial }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Student ID No:</strong> {{ $student->student_id_no }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Email:</strong> {{ $student->email ?? 'N/A' }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Contact No:</strong> {{ $student->contact_no ?? 'N/A' }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">FB Link:</strong> {{ $student->fb_link ?? 'N/A' }}</li>
                </ul>
            </div>
            <div class="col-md-6 mb-4">
                <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3">Academic & Scholarship Info</h6>
                <ul class="list-group">
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">TDP-TES Award No:</strong> {{ $student->tdp_tes_award_no }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Degree Program:</strong> {{ $student->degree_program ?? 'N/A' }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Year Level:</strong> {{ $student->year_level ?? 'N/A' }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">PWD No:</strong> {{ $student->pwd_no }}</li>
                  <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">IP No:</strong> {{ $student->ip_no }}</li>
                </ul>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
