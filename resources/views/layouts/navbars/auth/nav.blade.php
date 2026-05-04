<!-- Navbar -->
<nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur" navbar-scroll="true">
    <div class="container-fluid py-1 px-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
            <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
            <li class="breadcrumb-item text-sm text-dark active text-capitalize" aria-current="page">{{ str_replace('-', ' ', Request::path()) }}</li>
            </ol>
            <h6 class="font-weight-bolder mb-0 text-capitalize">{{ str_replace('-', ' ', Request::path()) }}</h6>
        </nav>
        <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4 d-flex justify-content-end" id="navbar">
            @php
              $todayActivity = \Illuminate\Support\Facades\DB::table('billing_batch')
                ->where('delete_status', '0')
                ->whereDate('created_at', \Illuminate\Support\Carbon::today())
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
              $todayActivityCount = \Illuminate\Support\Facades\DB::table('billing_batch')
                ->where('delete_status', '0')
                ->whereDate('created_at', \Illuminate\Support\Carbon::today())
                ->count();
            @endphp
            <ul class="navbar-nav  justify-content-end align-items-center">
            <li class="nav-item dropdown px-3 d-flex align-items-center">
                <a href="javascript:;" class="nav-link p-0 position-relative text-secondary" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false" style="color: #67748e !important;">
                    <i class="fa fa-bell cursor-pointer" style="font-size: 1.1rem;"></i>
                    @if($todayActivityCount > 0)
                        <span class="position-absolute top-5 start-100 translate-middle badge rounded-pill bg-danger border border-white" style="font-size: 0.5rem; padding: 0.35em 0.5em; z-index: 1;">
                            {{ $todayActivityCount }}
                        </span>
                    @endif
                </a>
                <ul class="dropdown-menu dropdown-menu-end px-2 py-3 me-sm-n4 shadow-lg border-radius-xl" aria-labelledby="dropdownMenuButton" style="min-width: 320px; background: #ffffff; border: 1px solid #e9ecef; margin-top: 1rem !important;">
                    <div class="d-flex justify-content-between align-items-center mb-3 px-2">
                        <h6 class="text-sm font-weight-bolder mb-0 text-dark">Recent Activity</h6>
                        <span class="badge badge-sm bg-gradient-info">{{ $todayActivityCount }} New</span>
                    </div>
                    @forelse($todayActivity as $activity)
                    <li class="mb-2">
                        <a class="dropdown-item border-radius-md px-3 py-2 transition-all hover-bg-light" href="{{ route('scholarship-billing.index', ['program' => $activity->program]) }}" style="transition: all 0.2s ease;">
                            <div class="d-flex align-items-center">
                                <div class="icon icon-shape icon-sm bg-gradient-primary text-center border-radius-md d-flex align-items-center justify-content-center me-3 shadow-none" style="border: 1px solid rgba(0,0,0,0.05);">
                                    <i class="fas fa-file-invoice text-white opacity-10" style="font-size: 0.75rem;"></i>
                                </div>
                                <div class="d-flex flex-column justify-content-center flex-grow-1">
                                    <h6 class="text-sm font-weight-bold mb-0 text-dark">
                                        {{ $activity->program }}
                                    </h6>
                                    <p class="text-xs text-secondary mb-0 d-flex align-items-center">
                                        <span class="p-1 bg-success rounded-circle me-1" style="width: 4px; height: 4px;"></span>
                                        {{ \Illuminate\Support\Carbon::parse($activity->created_at)->setTimezone(config('app.timezone', 'Asia/Manila'))->diffForHumans() }}
                                    </p>
                                </div>
                                <i class="fas fa-chevron-right text-xs text-secondary opacity-5"></i>
                            </div>
                        </a>
                    </li>
                    @empty
                    <li class="text-center py-4">
                        <div class="icon icon-shape icon-lg bg-gray-100 shadow-none text-center border-radius-xl d-flex align-items-center justify-content-center mx-auto mb-2">
                            <i class="fas fa-bell-slash text-secondary opacity-5"></i>
                        </div>
                        <p class="text-xs text-secondary mb-0">No new activity for today.</p>
                    </li>
                    @endforelse
                    
                    @if($todayActivityCount > 0)
                    <div class="mt-3 text-center border-top pt-2">
                        <a class="btn btn-link btn-sm text-info font-weight-bold mb-0 w-100" href="{{ route('scholarship-billing.index') }}">
                            View All Activity <i class="fas fa-arrow-right ms-1 text-xs"></i>
                        </a>
                    </div>
                    @endif
                </ul>
            </li>
            <li class="nav-item d-flex align-items-center">
                <a href="{{ url('/logout')}}" class="nav-link text-body font-weight-bold px-0">
                    <i class="fa fa-sign-out-alt me-sm-1"></i>
                    <span class="d-sm-inline d-none">Sign Out</span>
                </a>
            </li>
            <li class="nav-item d-xl-none ps-3 d-flex align-items-center">
                <a href="javascript:;" class="nav-link text-body p-0" id="iconNavbarSidenav">
                <div class="sidenav-toggler-inner">
                    <i class="sidenav-toggler-line"></i>
                    <i class="sidenav-toggler-line"></i>
                    <i class="sidenav-toggler-line"></i>
                </div>
                </a>
            </li>
            </ul>
        </div>
    </div>
</nav>
<!-- End Navbar -->