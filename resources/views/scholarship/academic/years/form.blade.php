@extends('layouts.user_type.auth')

@section('content')

@php
  $rowData = $row ? (array) $row : [];
  $isEdit = !empty($rowData);
  $title = $isEdit ? 'Edit Academic Year' : 'Add Academic Year';
@endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body d-md-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-1">{{ $title }}</h5>
          <p class="text-sm mb-0">Changes to label values propagate to linked scholarship records.</p>
        </div>
        <a href="{{ route('scholarship-academic.years.index') }}" class="btn btn-outline-dark mb-0">Back To Academic Years</a>
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

        <form method="POST" action="{{ $isEdit ? route('scholarship-academic.years.update', $rowData['id']) : route('scholarship-academic.years.store') }}">
          @csrf
          @if ($isEdit)
            @method('PUT')
          @endif

          <div class="mb-3">
            <label class="form-label">Academic Year Label</label>
            <input type="text" name="label" class="form-control" value="{{ old('label', $rowData['label'] ?? '') }}" placeholder="e.g. 2025-2026" required />
          </div>

          <div class="d-flex">
            <button type="submit" class="btn bg-gradient-primary mb-0 me-2">Save Academic Year</button>
            <a href="{{ route('scholarship-academic.years.index') }}" class="btn btn-outline-secondary mb-0">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection
