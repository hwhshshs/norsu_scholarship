<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3" id="sidenav-main" style="overflow-y: auto !important; max-height: none !important;">
  <div class="sidenav-header">
    <i class="fas fa-times p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
    <a class="align-items-center d-flex m-0 navbar-brand text-wrap" href="{{ route('dashboard') }}">
      <img src="{{ asset('assets/img/norsu_logo.png') }}" class="navbar-brand-img h-100" alt="NORSU Logo">
      <span class="ms-3 font-weight-bold" style="color: #003366;">NORSU SCHOLARSHIP</span>
    </a>
  </div>
  <hr class="horizontal dark mt-0">
  <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main" style="height: auto !important; overflow: hidden !important;">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link {{ (Request::is('dashboard') ? 'active' : '') }}" href="{{ route('dashboard') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i style="font-size: 0.9rem;" class="fas fa-home text-center {{ (Request::is('dashboard') ? 'text-white' : 'text-dark') }}"></i>
          </div>
          <span class="nav-link-text ms-1">Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ (Request::is('student-info*') ? 'active' : '') }}" href="{{ route('student-info.index') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i style="font-size: 0.9rem;" class="fas fa-users text-center {{ (Request::is('student-info*') ? 'text-white' : 'text-dark') }}"></i>
          </div>
          <span class="nav-link-text ms-1">Student Info</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ (Request::is('billing*') ? 'active' : '') }}" href="{{ route('billing.index') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i style="font-size: 0.9rem;" class="fas fa-file-invoice-dollar text-center {{ (Request::is('billing*') ? 'text-white' : 'text-dark') }}"></i>
          </div>
          <span class="nav-link-text ms-1">Billing</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ (Request::is('disbursement*') ? 'active' : '') }}" href="{{ route('disbursement.index') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i style="font-size: 0.9rem;" class="fas fa-money-check-alt text-center {{ (Request::is('disbursement*') ? 'text-white' : 'text-dark') }}"></i>
          </div>
          <span class="nav-link-text ms-1">Disbursement</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ (Request::is('fund-report*') ? 'active' : '') }}" href="{{ route('fund-report.index') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i style="font-size: 0.9rem;" class="fas fa-chart-bar text-center {{ (Request::is('fund-report*') ? 'text-white' : 'text-dark') }}"></i>
          </div>
          <span class="nav-link-text ms-1">Fund Report</span>
        </a>
      </li>

      @if(auth()->check() && auth()->user()->role === 'admin')
      <li class="nav-item mt-3">
        <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">Administration</h6>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ (Request::is('admin/activity-logs*') ? 'active' : '') }}" href="{{ route('admin.activity-logs') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i style="font-size: 0.9rem;" class="fas fa-list-ul text-center {{ (Request::is('admin/activity-logs*') ? 'text-white' : 'text-dark') }}"></i>
          </div>
          <span class="nav-link-text ms-1">Activity Logs</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ (Request::is('admin/staff*') ? 'active' : '') }}" href="{{ route('admin.staff.index') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i style="font-size: 0.9rem;" class="fas fa-user-shield text-center {{ (Request::is('admin/staff*') ? 'text-white' : 'text-dark') }}"></i>
          </div>
          <span class="nav-link-text ms-1">Staff Management</span>
        </a>
      </li>
      @endif
    </ul>
  </div>
</aside>
