@extends('layouts.user_type.auth')

@section('content')

@php
  $stats = $scholarshipStats ?? [];
  $totalStudents = $stats['total_students'] ?? 0;
  $activeStudents = $stats['active_students'] ?? 0;
  $inactiveStudents = $stats['inactive_students'] ?? 0;
  $yearLevels = $stats['year_levels'] ?? 0;
  $billingBatches = $stats['billing_batches'] ?? 0;
  $disbursedFinalized = $stats['disbursed_finalized'] ?? 0;
  $billedScholars = $stats['billed_scholars'] ?? 0;
  $disbursedScholars = $stats['disbursed_scholars'] ?? 0;
  $moduleCount = $stats['module_count'] ?? 0;
  $totalPayout = number_format((float) ($stats['total_payout'] ?? 0), 2);
@endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body">
        <div>
          <h5 class="mb-1">Scholarship Management Modules</h5>
          <p class="text-sm mb-0">Manage students, academics, fund reporting, and reconciliation in one workspace.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
    <div class="card">
      <div class="card-body p-3">
        <div class="row">
          <div class="col-8">
            <div class="numbers">
              <p class="text-sm mb-0 text-capitalize font-weight-bold">Total Students</p>
              <h5 class="font-weight-bolder mb-0">{{ number_format($totalStudents) }}</h5>
            </div>
          </div>
          <div class="col-4 text-end">
            <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
              <i class="ni ni-single-02 text-lg opacity-10" aria-hidden="true"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
    <div class="card">
      <div class="card-body p-3">
        <div class="row">
          <div class="col-8">
            <div class="numbers">
              <p class="text-sm mb-0 text-capitalize font-weight-bold">Active Students</p>
              <h5 class="font-weight-bolder mb-0">{{ number_format($activeStudents) }}</h5>
            </div>
          </div>
          <div class="col-4 text-end">
            <div class="icon icon-shape bg-gradient-success shadow text-center border-radius-md">
              <i class="ni ni-check-bold text-lg opacity-10" aria-hidden="true"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
    <div class="card">
      <div class="card-body p-3">
        <div class="row">
          <div class="col-8">
            <div class="numbers">
              <p class="text-sm mb-0 text-capitalize font-weight-bold">Inactive Students</p>
              <h5 class="font-weight-bolder mb-0">{{ number_format($inactiveStudents) }}</h5>
            </div>
          </div>
          <div class="col-4 text-end">
            <div class="icon icon-shape bg-gradient-warning shadow text-center border-radius-md">
              <i class="ni ni-fat-remove text-lg opacity-10" aria-hidden="true"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="card">
      <div class="card-body p-3">
        <div class="row">
          <div class="col-8">
            <div class="numbers">
              <p class="text-sm mb-0 text-capitalize font-weight-bold">Total Payout</p>
              <h5 class="font-weight-bolder mb-0">{{ $totalPayout }}</h5>
            </div>
          </div>
          <div class="col-4 text-end">
            <div class="icon icon-shape bg-gradient-info shadow text-center border-radius-md">
              <i class="ni ni-money-coins text-lg opacity-10" aria-hidden="true"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mt-4">
  <div class="col-xl-4 col-sm-6 mb-xl-0 mb-4">
    <div class="card">
      <div class="card-body p-3">
        <div class="row">
          <div class="col-8">
            <div class="numbers">
              <p class="text-sm mb-0 text-capitalize font-weight-bold">Billed Scholars</p>
              <h5 class="font-weight-bolder mb-0">{{ number_format($billedScholars) }}</h5>
            </div>
          </div>
          <div class="col-4 text-end">
            <div class="icon icon-shape bg-gradient-warning shadow text-center border-radius-md">
              <i class="ni ni-credit-card text-lg opacity-10" aria-hidden="true"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-sm-6 mb-xl-0 mb-4">
    <div class="card">
      <div class="card-body p-3">
        <div class="row">
          <div class="col-8">
            <div class="numbers">
              <p class="text-sm mb-0 text-capitalize font-weight-bold">Disbursed Scholars</p>
              <h5 class="font-weight-bolder mb-0">{{ number_format($disbursedScholars) }}</h5>
            </div>
          </div>
          <div class="col-4 text-end">
            <div class="icon icon-shape bg-gradient-info shadow text-center border-radius-md">
              <i class="ni ni-delivery-fast text-lg opacity-10" aria-hidden="true"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mt-4">
  <div class="col-lg-8 mb-lg-0 mb-4">
    <div class="card h-100">
      <div class="card-header pb-0">
        <h6 class="mb-0">Quick Launch</h6>
        <p class="text-sm mb-0">Open the most used scholarship modules directly from the dashboard.</p>
      </div>
      <div class="card-body pt-3">
        <div class="row">
          @foreach (($moduleHighlights ?? []) as $module)
            @if (in_array($module['slug'], ['disbursed-report', 'legacy-dashboard', 'legacy-login'], true))
              @continue
            @endif
            @php
              $moduleName = $module['name'];
              $moduleDescription = $module['description'];

              $moduleUrl = $module['slug'] === 'students'
                ? route('scholarship-students.index')
                : route('scholarship-system.module', $module['slug']);

              if ($module['slug'] === 'legacy-dashboard') {
                $moduleUrl = route('dashboard');
              } elseif ($module['slug'] === 'legacy-login') {
                $moduleUrl = route('scholarship-system.launch', 'legacy-login');
              } elseif ($module['slug'] === 'inactive-students') {
                $moduleUrl = route('scholarship-students.index', ['status' => 'inactive']);
              } elseif ($module['slug'] === 'student-report') {
                $moduleUrl = route('scholarship-students.report');
              } elseif ($module['slug'] === 'academic-management') {
                $moduleUrl = route('scholarship-academic.index');
              } elseif ($module['slug'] === 'billing-report') {
                $moduleUrl = route('scholarship-fund-report.index');
                $moduleName = 'Fund Report';
                $moduleDescription = 'Unified billing and disbursed report view in one module.';
              } elseif ($module['slug'] === 'disbursed-report') {
                $moduleUrl = route('scholarship-fund-report.index');
              } elseif ($module['slug'] === 'disbursed-entry') {
                $moduleUrl = route('scholarship-disbursed.entry.form');
              } elseif ($module['slug'] === 'reconciliation') {
                $moduleUrl = route('scholarship-reconciliation.index');
              } elseif ($module['slug'] === 'account-setting') {
                $moduleUrl = route('scholarship-account-setting.index');
              }
            @endphp
            <div class="col-md-6 mb-3">
              <div class="border border-radius-md p-3 h-100 d-flex flex-column">
                <div class="d-flex align-items-center mb-2">
                  <div class="icon icon-shape icon-sm bg-gradient-dark shadow text-center border-radius-md me-2">
                    <i class="{{ $module['icon'] }} text-white"></i>
                  </div>
                  <h6 class="text-sm mb-0">{{ $moduleName }}</h6>
                </div>
                <p class="text-xs text-secondary mb-3">{{ $moduleDescription }}</p>
                <a href="{{ $moduleUrl }}" class="btn btn-sm bg-gradient-primary mb-0 mt-auto">Open Module</a>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header pb-0">
        <h6 class="mb-0">Operations Snapshot</h6>
      </div>
      <div class="card-body pt-3">
        <ul class="list-group">
          <li class="list-group-item border-0 ps-0 text-sm">Configured Modules: <strong>{{ number_format($moduleCount) }}</strong></li>
          <li class="list-group-item border-0 ps-0 text-sm">Year Levels: <strong>{{ number_format($yearLevels) }}</strong></li>
          <li class="list-group-item border-0 ps-0 text-sm">Billing Batches: <strong>{{ number_format($billingBatches) }}</strong></li>
          <li class="list-group-item border-0 ps-0 text-sm">Finalized Disbursed Rows: <strong>{{ number_format($disbursedFinalized) }}</strong></li>
        </ul>
        <a href="{{ route('scholarship-reconciliation.index') }}" class="btn btn-outline-dark btn-sm mb-0 mt-2 me-2">Open Reconciliation</a>
      </div>
    </div>
  </div>
</div>

@endsection
