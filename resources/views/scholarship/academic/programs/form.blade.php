@extends('layouts.user_type.auth')

@section('content')

@php
  $rowData = $row ? (array) $row : [];
  $isEdit = !empty($rowData);
  $title = $isEdit ? 'Edit Program' : 'Add Program';
@endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body d-md-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-1">{{ $title }}</h5>
          <p class="text-sm mb-0">Program name changes propagate to linked student, billing, and disbursed records.</p>
        </div>
        <a href="{{ route('scholarship-academic.programs.index') }}" class="btn btn-outline-dark mb-0">Back To Programs</a>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        @if ($errors->any())
          <div class="alert alert-danger text-white" role="alert">
            <strong>Please fix the following:</strong>
            <ul class="mb-0 mt-2">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form method="POST" action="{{ $isEdit ? route('scholarship-academic.programs.update', $rowData['id']) : route('scholarship-academic.programs.store') }}">
          @csrf
          @if ($isEdit)
            @method('PUT')
          @endif

          <div class="mb-3">
            <label class="form-label">Program Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $rowData['name'] ?? '') }}" placeholder="e.g. TDP-TES" required />
          </div>

          <div class="d-flex">
            <button type="submit" class="btn bg-gradient-primary mb-0 me-2">Save Program</button>
            <a href="{{ route('scholarship-academic.programs.index') }}" class="btn btn-outline-secondary mb-0">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection
