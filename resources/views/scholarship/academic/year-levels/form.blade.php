@extends('layouts.user_type.auth')

@section('content')

@php
  $rowData = $row ? (array) $row : [];
  $isEdit = !empty($rowData);
  $title = $isEdit ? 'Edit Year Level' : 'Add Year Level';
  $defaultLabel = trim((string) ($rowData['year_level'] ?? '')) !== '' ? $rowData['year_level'] : ($rowData['grade'] ?? '');
@endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body d-md-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-1">{{ $title }}</h5>
          <p class="text-sm mb-0">Year levels are used by student records for scholarship tagging and filtering.</p>
        </div>
        <a href="{{ route('scholarship-academic.year-levels.index') }}" class="btn btn-outline-dark mb-0">Back To Year Levels</a>
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

        <form method="POST" action="{{ $isEdit ? route('scholarship-academic.year-levels.update', $rowData['id']) : route('scholarship-academic.year-levels.store') }}">
          @csrf
          @if ($isEdit)
            @method('PUT')
          @endif

          <div class="mb-3">
            <label class="form-label">Year Level</label>
            <input type="text" name="year_level" class="form-control" value="{{ old('year_level', $defaultLabel) }}" placeholder="e.g. 1st Year" required />
          </div>

          <div class="mb-3">
            <label class="form-label">Detail</label>
            <textarea name="detail" class="form-control" rows="3">{{ old('detail', $rowData['detail'] ?? '') }}</textarea>
          </div>

          <div class="d-flex">
            <button type="submit" class="btn bg-gradient-primary mb-0 me-2">Save Year Level</button>
            <a href="{{ route('scholarship-academic.year-levels.index') }}" class="btn btn-outline-secondary mb-0">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection
