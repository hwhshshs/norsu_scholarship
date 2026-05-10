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
              $todayActivity = \Illuminate\Support\Facades\DB::table('billing_batches')
                ->whereDate('created_at', \Illuminate\Support\Carbon::today())
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
              $todayActivityCount = \Illuminate\Support\Facades\DB::table('billing_batches')
                ->whereDate('created_at', \Illuminate\Support\Carbon::today())
                ->count();
              $importHistory = \Illuminate\Support\Facades\DB::table('import_summaries')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
            @endphp
            <ul class="navbar-nav  justify-content-end align-items-center">
            <li class="nav-item dropdown pe-2 d-flex align-items-center">
                <a href="javascript:;" class="nav-link p-0 position-relative" id="intelligenceDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="color: #002d54 !important; width: 42px; height: 35px; display: flex; align-items: center; justify-content: center; overflow: visible !important;">
                    <i class="fas fa-robot cursor-pointer" style="font-size: 1.2rem;"></i>
                    @if(session('import_report'))
                    <span class="position-absolute badge rounded-pill bg-info border border-white" style="top: -2px; right: 0px; font-size: 0.45rem; padding: 0.25em 0.5em; z-index: 1;">
                        NEW
                    </span>
                    @endif
                </a>
                <ul class="dropdown-menu dropdown-menu-end px-2 py-3 me-sm-n4 shadow-lg border-radius-xl" aria-labelledby="intelligenceDropdown" style="min-width: 350px; background: #ffffff; border: 1px solid #e9ecef; margin-top: 1rem !important; max-height: 500px; overflow-y: auto;">
                    @if(session('import_report'))
                        <div class="px-3 mb-3 border-bottom pb-3">
                            <h6 class="text-sm font-weight-bolder mb-0 text-dark">Latest Audit Summary</h6>
                            <p class="text-xxs text-secondary mb-3">Audit for: {{ session('import_report')['total_rows'] }} records</p>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="p-2 bg-gray-100 border-radius-md text-center cursor-pointer hover-bg-light transition-all" onclick='showImportDetails(@json(session("import_report")), "success")'>
                                        <h6 class="text-xs font-weight-bolder text-success mb-0">{{ session('import_report')['success'] }}</h6>
                                        <p class="text-xxs text-secondary mb-0">New</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 bg-gray-100 border-radius-md text-center cursor-pointer hover-bg-light transition-all" onclick='showImportDetails(@json(session("import_report")), "duplicate")'>
                                        <h6 class="text-xs font-weight-bolder mb-0" style="color: #002d54;">{{ session('import_report')['duplicate'] }}</h6>
                                        <p class="text-xxs text-secondary mb-0">Existing</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 bg-gray-100 border-radius-md text-center cursor-pointer hover-bg-light transition-all" onclick='showImportDetails(@json(session("import_report")), "conflict")'>
                                        <h6 class="text-xs font-weight-bolder text-warning mb-0">{{ session('import_report')['conflict'] }}</h6>
                                        <p class="text-xxs text-secondary mb-0">Blocked</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 bg-gray-100 border-radius-md text-center cursor-pointer hover-bg-light transition-all" onclick='showImportDetails(@json(session("import_report")), "invalid")'>
                                        <h6 class="text-xs font-weight-bolder text-danger mb-0">{{ session('import_report')['invalid'] }}</h6>
                                        <p class="text-xxs text-secondary mb-0">Invalid</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="px-3">
                        <h6 class="text-xs font-weight-bolder text-uppercase text-secondary mb-3">Intelligence Timeline</h6>
                        <div class="timeline timeline-one-side">
                            @forelse($importHistory as $history)
                            <div class="timeline-block mb-3">
                                <span class="timeline-step">
                                    <i class="fas fa-history text-info text-gradient"></i>
                                </span>
                                <div class="timeline-content cursor-pointer" onclick='viewHistorySummary({{ $history->id }})'>
                                    <h6 class="text-dark text-sm font-weight-bold mb-0">{{ $history->program }} ({{ $history->ay }})</h6>
                                    <p class="text-secondary font-weight-bold text-xxs mt-1 mb-0">
                                        {{ \Illuminate\Support\Carbon::parse($history->created_at)->format('M d, g:i A') }} • 
                                        <span class="text-success">{{ $history->success_count }} Added</span> • 
                                        <span class="text-warning">{{ $history->conflict_count }} Blocked</span>
                                    </p>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-3">
                                <p class="text-xs text-secondary mb-0">No historical audit logs found.</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-3 text-center border-top pt-2">
                        <a href="javascript:;" class="text-xxs text-secondary font-weight-bold opacity-7">Full Audit History</a>
                    </div>
                </ul>
            </li>

            <li class="nav-item dropdown px-2 d-flex align-items-center">
                <a href="javascript:;" class="nav-link p-0 position-relative" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false" style="color: #000000 !important; width: 32px; height: 35px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-bell cursor-pointer" style="font-size: 1.2rem;"></i>
                    @if($todayActivityCount > 0)
                        <span class="position-absolute badge rounded-circle bg-dark border border-white" style="top: 2px; right: -2px; font-size: 0.55rem; min-width: 16px; height: 16px; padding: 0; display: flex; align-items: center; justify-content: center; z-index: 1;">
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
                        <a class="dropdown-item border-radius-md px-3 py-2 transition-all hover-bg-light" href="{{ route('billing.index', ['program' => $activity->program]) }}" style="transition: all 0.2s ease;">
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
                        <a class="btn btn-link btn-sm text-info font-weight-bold mb-0 w-100" href="{{ route('billing.index') }}">
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

<!-- Import Details Modal -->
<div class="modal fade" id="importDetailsModal" tabindex="-1" aria-labelledby="importDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem;">
      <div class="modal-header border-0 p-4">
        <div>
            <h5 class="modal-title font-weight-bold" id="importDetailsTitle" style="color: #002d54;">Audit Details</h5>
            <p class="text-xs text-secondary mb-0" id="importDetailsSubtitle">Reviewing scholars in this category</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 pt-0">
        <div class="table-responsive" style="max-height: 400px; border-radius: 0.5rem; border: 1px solid #f0f2f5;">
          <table class="table table-hover align-items-center mb-0">
            <thead class="bg-gray-100">
              <tr>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 px-4">Scholar Name</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Student ID</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" id="reasonHeader" style="display:none;">Status/Reason</th>
              </tr>
            </thead>
            <tbody id="importDetailsTableBody">
                <!-- Populated by JS -->
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer border-0 p-4 pt-0">
        <button type="button" class="btn bg-gray-100 text-xxs px-4 mb-0 me-2" onclick="backToSummary()">
            <i class="fas fa-arrow-left me-1"></i> Back to Summary
        </button>
        <button type="button" class="btn btn-secondary text-xxs px-4 mb-0" data-bs-dismiss="modal">Close Audit</button>
      </div>
    </div>
  </div>
</div>

<script>
let currentAuditReport = null;
let summaryModalInstance = null;
let detailsModalInstance = null;

function viewHistorySummary(id) {
    const history = @json($importHistory);
    const item = history.find(h => h.id == id);
    if (item) {
        const reportData = JSON.parse(item.report_data);
        currentAuditReport = reportData;
        
        document.getElementById('histTotalRows').innerText = `${reportData.total_rows || 0} Records`;
        document.getElementById('histAuditTitle').innerText = `${item.program} Audit`;
        document.getElementById('histAuditSubtitle').innerText = `${item.filename} • ${new Date(item.created_at).toLocaleString()}`;
        
        document.getElementById('histSuccessCount').innerText = reportData.success || 0;
        document.getElementById('histDuplicateCount').innerText = reportData.duplicate || 0;
        document.getElementById('histConflictCount').innerText = reportData.conflict || 0;
        document.getElementById('histInvalidCount').innerText = reportData.invalid || 0;

        const conflictBox = document.getElementById('histConflictExplanation');
        if (reportData.success == 0 && (reportData.total_rows > 0)) {
            conflictBox.style.display = 'block';
        } else {
            conflictBox.style.display = 'none';
        }

        if(!summaryModalInstance) summaryModalInstance = new bootstrap.Modal(document.getElementById('historySummaryModal'));
        summaryModalInstance.show();
    }
}

function showImportDetails(report, type, customTitle = null) {
    const activeReport = (typeof report === 'string' || !report) ? currentAuditReport : report;
    if (!activeReport) return;

    if (summaryModalInstance) summaryModalInstance.hide();

    let list = [];
    let title = "";
    let subtitle = "";
    let showReason = false;

    switch(type) {
        case 'success':
            list = activeReport.success_list || [];
            title = customTitle || "Newly Added Scholars";
            subtitle = "Scholars successfully added to the system.";
            break;
        case 'duplicate':
            list = activeReport.duplicate_list || [];
            title = customTitle || "Existing Scholars (Skipped)";
            subtitle = "Scholars who were already present in this specific batch.";
            break;
        case 'conflict':
            list = activeReport.conflict_list || [];
            title = customTitle || "Blocked Conflicts";
            subtitle = "Blocked to prevent double-funding in the same period.";
            showReason = true;
            break;
        case 'invalid':
            list = activeReport.invalid_list || [];
            title = customTitle || "Invalid Rows Detected";
            subtitle = "Rows that could not be read due to missing data.";
            break;
    }

    document.getElementById('importDetailsTitle').innerText = title;
    document.getElementById('importDetailsSubtitle').innerText = subtitle;
    document.getElementById('reasonHeader').style.display = showReason ? 'table-cell' : 'none';

    const tbody = document.getElementById('importDetailsTableBody');
    tbody.innerHTML = '';

    if (list.length === 0) {
        const isLegacy = !activeReport.success_list && !activeReport.duplicate_list;
        tbody.innerHTML = `<tr><td colspan="3" class="text-center py-4">
            <p class="text-xs text-secondary mb-0">
                ${isLegacy ? 'Detailed tracking not available for this legacy record.' : 'No specific details available for this category.'}
            </p>
        </td></tr>`;
    } else {
        list.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-4 py-3">
                    <h6 class="mb-0 text-xs font-weight-bold">${item.name}</h6>
                </td>
                <td class="py-3">
                    <span class="text-xs text-secondary font-weight-bold">${item.id}</span>
                </td>
                ${showReason ? `<td class="py-3"><span class="badge badge-sm bg-soft-warning text-warning text-xxs">${item.reason || 'Double-Funded'}</span></td>` : ''}
            `;
            tbody.appendChild(tr);
        });
    }

    if(!detailsModalInstance) detailsModalInstance = new bootstrap.Modal(document.getElementById('importDetailsModal'));
    detailsModalInstance.show();
}

function backToSummary() {
    if(detailsModalInstance) detailsModalInstance.hide();
    if(summaryModalInstance) summaryModalInstance.show();
}
</script>

<!-- History Summary Modal -->
<div class="modal fade" id="historySummaryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem;">
      <div class="modal-header border-0 p-4 pb-0">
        <div>
            <h5 class="modal-title font-weight-bold" id="histAuditTitle" style="color: #002d54;">Import Audit</h5>
            <p class="text-xs text-secondary mb-0" id="histAuditSubtitle">Historical audit log</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <p class="text-xxs text-uppercase text-secondary font-weight-bold mb-3" id="histTotalRows">0 Records</p>
        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="p-3 bg-gray-100 border-radius-lg text-center cursor-pointer hover-bg-light transition-all" onclick="showImportDetails(null, 'success')">
                    <h4 class="font-weight-bolder text-success mb-0" id="histSuccessCount">0</h4>
                    <p class="text-xxs text-secondary mb-0">Newly Added</p>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 bg-gray-100 border-radius-lg text-center cursor-pointer hover-bg-light transition-all" onclick="showImportDetails(null, 'duplicate')">
                    <h4 class="font-weight-bolder mb-0" style="color: #002d54;" id="histDuplicateCount">0</h4>
                    <p class="text-xxs text-secondary mb-0">Existing</p>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 bg-gray-100 border-radius-lg text-center cursor-pointer hover-bg-light transition-all" onclick="showImportDetails(null, 'conflict')">
                    <h4 class="font-weight-bolder text-warning mb-0" id="histConflictCount">0</h4>
                    <p class="text-xxs text-secondary mb-0">Blocked</p>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 bg-gray-100 border-radius-lg text-center cursor-pointer hover-bg-light transition-all" onclick="showImportDetails(null, 'invalid')">
                    <h4 class="font-weight-bolder text-danger mb-0" id="histInvalidCount">0</h4>
                    <p class="text-xxs text-secondary mb-0">Invalid</p>
                </div>
            </div>
        </div>
        <div id="histConflictExplanation" style="display:none;">
            <div class="p-3 border-radius-md" style="background-color: rgba(0, 45, 84, 0.05);">
                <p class="text-xxs mb-0 font-weight-bold" style="color: #002d54; line-height: 1.4;">
                    <i class="fas fa-info-circle me-1"></i>
                    All records in this import were either duplicates or blocked.
                </p>
            </div>
        </div>
        <p class="text-center text-xxs text-secondary mt-3 mb-0 italic">Click a box to see the list of scholars</p>
      </div>
    </div>
  </div>
</div>
<!-- End Navbar -->