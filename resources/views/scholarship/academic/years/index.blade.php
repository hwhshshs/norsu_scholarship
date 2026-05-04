@extends('layouts.user_type.auth')

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body d-md-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-1">Academic Years</h5>
          <p class="text-sm mb-0">Create, update, activate, deactivate, and safely remove academic year records.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex flex-wrap">
          <a href="{{ route('scholarship-academic.years.create') }}" class="btn bg-gradient-primary mb-0 me-2">Add Academic Year</a>
          <a href="{{ route('scholarship-academic.index') }}" class="btn btn-outline-dark mb-0 me-2">Back To Academic</a>
          <a href="{{ route('scholarship-system.module', 'academic-year') }}" class="btn btn-outline-primary mb-0">Legacy Page</a>
        </div>
      </div>
    </div>
  </div>
</div>

@if (session('success'))
  <div class="row">
    <div class="col-12">
      <div class="alert alert-success text-white" role="alert">{{ session('success') }}</div>
    </div>
  </div>
@endif

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header pb-0">
        <h6 class="mb-0">Search and Filter</h6>
      </div>
      <div class="card-body pt-3">
        <form method="GET" action="{{ route('scholarship-academic.years.index') }}" class="row g-3 align-items-end">
          <div class="col-md-6">
            <label class="form-label">Search</label>
            <input type="text" name="q" class="form-control" value="{{ $search }}" placeholder="Academic year label" />
          </div>
          <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
              <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
              <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
          </div>
          <div class="col-md-3 d-flex">
            <button type="submit" class="btn bg-gradient-dark mb-0 me-2">Apply</button>
            <a href="{{ route('scholarship-academic.years.index') }}" class="btn btn-outline-secondary mb-0">Reset</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header pb-0">
        <h6 class="mb-0">Academic Year Records</h6>
      </div>
      <div class="card-body px-0 pt-2 pb-0">
        <div class="table-responsive p-0">
          <table class="table align-items-center mb-0">
            <thead>
              <tr>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">Academic Year</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Created</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-end pe-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($rows as $row)
                @php
                  $isInactive = (string) ($row->delete_status ?? '0') === '1';
                @endphp
                <tr>
                  <td class="ps-3">
                    <p class="text-sm mb-0 font-weight-bold">{{ $row->label }}</p>
                  </td>
                  <td>
                    <span class="badge {{ $isInactive ? 'bg-gradient-secondary' : 'bg-gradient-success' }}">{{ $isInactive ? 'Inactive' : 'Active' }}</span>
                  </td>
                  <td>
                    <p class="text-sm mb-0">{{ $row->created_at ? \Illuminate\Support\Carbon::parse($row->created_at)->format('M d, Y') : '-' }}</p>
                  </td>
                  <td class="text-end pe-3">
                    <a href="{{ route('scholarship-academic.years.edit', $row->id) }}" class="btn btn-link text-dark px-2 mb-0">Edit</a>

                    <form method="POST" action="{{ route('scholarship-academic.years.toggle-status', $row->id) }}" class="d-inline">
                      @csrf
                      <input type="hidden" name="target_status" value="{{ $isInactive ? '0' : '1' }}" />
                      <button type="submit" class="btn btn-link {{ $isInactive ? 'text-success' : 'text-warning' }} px-2 mb-0">{{ $isInactive ? 'Activate' : 'Deactivate' }}</button>
                    </form>

                    <form method="POST" action="{{ route('scholarship-academic.years.remove', $row->id) }}" class="d-inline">
                      @csrf
                      <button type="submit" class="btn btn-link text-danger px-2 mb-0" onclick="confirmAction(event, 'Remove Item?', 'Are you sure you want to proceed with this removal?');">{{ $isInactive ? 'Delete' : 'Remove' }}</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-sm text-secondary py-4">No academic years found for the selected filters.</td>
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
