@extends('layouts.user_type.auth')

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body d-md-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-1">Scholarship System Feature Hub</h5>
          <p class="text-sm mb-0">All modules from the legacy scholarship system are available here. Open any module in this dashboard template without changing the existing UI style.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex align-items-center">
          <a href="{{ route('scholarship-system.checklist') }}" class="btn btn-sm btn-outline-dark mb-0 me-2">Integration Checklist</a>
          <span class="badge bg-gradient-dark">{{ $totalModules }} Modules</span>
        </div>
      </div>
    </div>
  </div>
</div>

@foreach ($moduleGroups as $groupName => $modules)
  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header pb-0">
          <h6 class="mb-0">{{ $groupName }}</h6>
        </div>
        <div class="card-body pt-3">
          <div class="row">
            @foreach ($modules as $module)
              @if ($module['slug'] === 'disbursed-report')
                @continue
              @endif
              @php
                $moduleName = $module['name'];
                $moduleDescription = $module['description'];

                $openHereUrl = $module['slug'] === 'students'
                  ? route('scholarship-students.index')
                  : route('scholarship-system.module', $module['slug']);

                if ($module['slug'] === 'legacy-dashboard') {
                  $openHereUrl = route('dashboard');
                } elseif ($module['slug'] === 'legacy-login') {
                  $openHereUrl = route('scholarship-system.launch', 'legacy-login');
                } elseif ($module['slug'] === 'inactive-students') {
                  $openHereUrl = route('scholarship-students.index', ['status' => 'inactive']);
                } elseif ($module['slug'] === 'student-report') {
                  $openHereUrl = route('scholarship-students.report');
                } elseif ($module['slug'] === 'academic-management') {
                  $openHereUrl = route('scholarship-academic.index');
                } elseif ($module['slug'] === 'academic-year') {
                  $openHereUrl = route('scholarship-academic.years.index');
                } elseif ($module['slug'] === 'year-levels') {
                  $openHereUrl = route('scholarship-academic.year-levels.index');
                } elseif ($module['slug'] === 'programs') {
                  $openHereUrl = route('scholarship-academic.programs.index');
                } elseif ($module['slug'] === 'billing-report') {
                  $openHereUrl = route('scholarship-fund-report.index');
                  $moduleName = 'Fund Report';
                  $moduleDescription = 'Unified billing and disbursed report view in one module.';
                } elseif ($module['slug'] === 'billing-entry') {
                  $openHereUrl = route('scholarship-billing.create');
                } elseif ($module['slug'] === 'disbursed-report') {
                  $openHereUrl = route('scholarship-fund-report.index');
                } elseif ($module['slug'] === 'disbursed-entry') {
                  $openHereUrl = route('scholarship-disbursed.entry.form');
                } elseif ($module['slug'] === 'disbursed-import') {
                  $openHereUrl = route('scholarship-disbursed.import.form');
                } elseif ($module['slug'] === 'reconciliation') {
                  $openHereUrl = route('scholarship-reconciliation.index');
                } elseif ($module['slug'] === 'account-setting') {
                  $openHereUrl = route('scholarship-account-setting.index');
                }
              @endphp
              <div class="col-xl-3 col-md-6 mb-4">
                <div class="border border-radius-md p-3 h-100 d-flex flex-column">
                  <div class="d-flex align-items-center mb-2">
                    <div class="icon icon-shape icon-sm bg-gradient-dark shadow text-center border-radius-md me-3">
                      <i class="{{ $module['icon'] }} text-white"></i>
                    </div>
                    <h6 class="mb-0 text-sm">{{ $moduleName }}</h6>
                  </div>
                  <p class="text-xs text-secondary mb-3">{{ $moduleDescription }}</p>
                  <div class="mt-auto d-flex">
                    <a href="{{ $openHereUrl }}" class="btn btn-sm bg-gradient-dark mb-0 me-2 w-100">Open Here</a>
                    <a href="{{ route('scholarship-system.launch', $module['slug']) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark mb-0 w-100">Legacy</a>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
@endforeach

@endsection
