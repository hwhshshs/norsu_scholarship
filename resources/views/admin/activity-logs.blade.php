@extends('layouts.user_type.auth')

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header pb-0">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">System Activity Logs</h6>
            <span class="badge badge-sm bg-gradient-info">{{ count($logs) }} Entries</span>
        </div>
      </div>
      <div class="card-body pt-4">
        <!-- Filter Bar -->
        <div class="bg-gray-100 border-radius-lg p-3 mb-4">
            <form method="GET" action="{{ route('admin.activity-logs') }}" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="module" class="form-control form-control-sm border-radius-md shadow-none border">
                        <option value="">All Modules</option>
                        <option value="Students" {{ request('module') == 'Students' ? 'selected' : '' }}>Students</option>
                        <option value="Billing" {{ request('module') == 'Billing' ? 'selected' : '' }}>Billing</option>
                        <option value="Disbursement" {{ request('module') == 'Disbursement' ? 'selected' : '' }}>Disbursement</option>
                        <option value="System" {{ request('module') == 'System' ? 'selected' : '' }}>System</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="input-group input-group-sm border-radius-md shadow-none border bg-white px-2">
                        <span class="input-group-text bg-transparent border-0 pe-0">
                            <i class="fas fa-search text-secondary text-xs"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-0 ps-2" value="{{ request('search') }}" placeholder="Search logs (Staff name, action, description...)">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm bg-gradient-dark mb-0 w-100 border-radius-md">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('admin.activity-logs') }}" class="btn btn-sm btn-outline-secondary mb-0 w-100 border-radius-md">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="table-responsive p-0">
          <table class="table align-items-center mb-0 table-hover">
            <thead>
              <tr>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">Time</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Staff</th>
                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Module</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Action</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Description</th>
              </tr>
            </thead>
            <tbody>
              @forelse($logs as $log)
              <tr>
                <td class="ps-3">
                  <span class="text-secondary text-xs font-weight-bold">{{ \Illuminate\Support\Carbon::parse($log->created_at)->format('M d, Y h:i A') }}</span>
                </td>
                <td>
                  <div class="d-flex py-1">
                    <div>
                      @php 
                        $staffName = $log->user_name ?? ($log->user_id ? 'Unknown Staff' : 'System');
                        $staffRole = $log->user_id ? 'Staff' : 'Automation';
                        $initials = strtoupper(substr($staffName, 0, 2));
                        $bg = $log->user_id ? 'info' : 'dark';
                      @endphp
                      <div class="avatar avatar-xs me-3 bg-gradient-{{ $bg }} border-radius-sm">
                        <span class="text-white text-xxs font-weight-bold">{{ $initials }}</span>
                      </div>
                    </div>
                    <div class="d-flex flex-column justify-content-center">
                      <h6 class="mb-0 text-xs font-weight-bold">{{ $staffName }}</h6>
                      <p class="text-xxs text-secondary mb-0">{{ $log->user_email ?? 'system@internal' }}</p>
                    </div>
                  </div>
                </td>
                <td class="align-middle text-center text-sm">
                  @php
                    $moduleColor = 'secondary';
                    if($log->module == 'Students') $moduleColor = 'info';
                    elseif($log->module == 'Billing') $moduleColor = 'warning';
                    elseif($log->module == 'Disbursement') $moduleColor = 'success';
                    elseif($log->module == 'System') $moduleColor = 'primary';
                  @endphp
                  <span class="badge badge-sm bg-gradient-{{ $moduleColor }} d-inline-flex align-items-center justify-content-center" style="width: 110px; height: 28px;">
                    {{ $log->module }}
                  </span>
                </td>
                <td>
                  <div class="d-flex align-items-center">
                    <p class="text-xs font-weight-bold mb-0 text-dark">{{ $log->action }}</p>
                    @if($log->file_path)
                      <a href="{{ route('admin.activity-logs.download', $log->id) }}" class="btn btn-link text-info p-0 mb-0 ms-2" title="Download original file: {{ $log->original_filename }}">
                        <i class="fas fa-file-download text-sm"></i>
                      </a>
                    @endif
                  </div>
                </td>
                <td class="align-middle">
                  <span class="text-secondary text-xs">{{ $log->description ?: '-' }}</span>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="5" class="text-center py-4">
                  <p class="text-xs text-secondary mb-0 font-weight-bold">No activity logs found matching your criteria.</p>
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
