<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3" id="sidenav-main">
  <div class="sidenav-header">
    <i class="fas fa-times p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
    <a class="align-items-center d-flex m-0 navbar-brand text-wrap" href="{{ route('dashboard') }}">
      <img src="{{ asset('assets/img/norsu_logo.png') }}" class="navbar-brand-img h-100" alt="NORSU Logo">
      <span class="ms-3 font-weight-bold" style="color: #003366;">NORSU SCHOLARSHIP</span>
    </a>
  </div>
  <hr class="horizontal dark mt-0">
  <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
    @php
      $isScholarshipHubActive = Request::is('scholarship-system')
        || Request::is('scholarship-system/module/*')
        || Request::is('scholarship-system/launch/*');
    @endphp
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link {{ (Request::is('dashboard') ? 'active' : '') }}" href="{{ url('dashboard') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i style="font-size: 0.9rem;" class="fas fa-home text-center {{ (Request::is('dashboard') ? 'text-white' : 'text-dark') }}"></i>
          </div>
          <span class="nav-link-text ms-1">Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $isScholarshipHubActive ? 'active' : '' }}" href="{{ route('scholarship-system') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i style="font-size: 0.95rem;" class="fas fa-graduation-cap text-center {{ $isScholarshipHubActive ? 'text-white' : 'text-dark' }}"></i>
          </div>
          <span class="nav-link-text ms-1">Scholarship Hub</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ ((Request::is('scholarship-system/students') || Request::is('scholarship-system/students/create') || Request::is('scholarship-system/students/import*') || Request::is('scholarship-system/students/*/edit')) ? 'active' : '') }}" href="{{ route('scholarship-students.index') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i style="font-size: 0.9rem;" class="fas fa-users text-center {{ ((Request::is('scholarship-system/students') || Request::is('scholarship-system/students/create') || Request::is('scholarship-system/students/import*') || Request::is('scholarship-system/students/*/edit')) ? 'text-white' : 'text-dark') }}"></i>
          </div>
          <span class="nav-link-text ms-1">Students</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ (Request::is('scholarship-system/students/report*') ? 'active' : '') }}" href="{{ route('scholarship-students.report') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i style="font-size: 0.9rem;" class="fas fa-file-alt text-center {{ (Request::is('scholarship-system/students/report*') ? 'text-white' : 'text-dark') }}"></i>
          </div>
          <span class="nav-link-text ms-1">Student Report</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ (Request::is('scholarship-system/academic*') ? 'active' : '') }}" href="{{ route('scholarship-academic.index') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i style="font-size: 0.9rem;" class="fas fa-book text-center {{ (Request::is('scholarship-system/academic*') ? 'text-white' : 'text-dark') }}"></i>
          </div>
          <span class="nav-link-text ms-1">Academic</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ ((Request::is('scholarship-system/fund-report*') || Request::is('scholarship-system/billing*') || Request::is('scholarship-system/disbursed*')) ? 'active' : '') }}" href="{{ route('scholarship-fund-report.index') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i style="font-size: 0.9rem;" class="fas fa-wallet text-center {{ ((Request::is('scholarship-system/fund-report*') || Request::is('scholarship-system/billing*') || Request::is('scholarship-system/disbursed*')) ? 'text-white' : 'text-dark') }}"></i>
          </div>
          <span class="nav-link-text ms-1">Fund Report</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ (Request::is('scholarship-system/reconciliation*') ? 'active' : '') }}" href="{{ route('scholarship-reconciliation.index') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i style="font-size: 0.9rem;" class="fas fa-exchange-alt text-center {{ (Request::is('scholarship-system/reconciliation*') ? 'text-white' : 'text-dark') }}"></i>
          </div>
          <span class="nav-link-text ms-1">Reconciliation</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ (Request::is('scholarship-system/account-setting*') ? 'active' : '') }}" href="{{ route('scholarship-account-setting.index') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i style="font-size: 0.9rem;" class="fas fa-cogs text-center {{ (Request::is('scholarship-system/account-setting*') ? 'text-white' : 'text-dark') }}"></i>
          </div>
          <span class="nav-link-text ms-1">Account Setting</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ (Request::is('scholarship-system/checklist') ? 'active' : '') }}" href="{{ route('scholarship-system.checklist') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i style="font-size: 0.9rem;" class="fas fa-clipboard-check text-center {{ (Request::is('scholarship-system/checklist') ? 'text-white' : 'text-dark') }}"></i>
          </div>
          <span class="nav-link-text ms-1">Integration Checklist</span>
        </a>
      </li>
    </ul>
  </div>
</aside>
