@extends('layouts.user_type.auth')

@section('content')

@php
  $totalStudents = $stats['scholars'] ?? 0;
  $totalBillingBatches = $stats['total_billing_batches'] ?? 0;
  $totalAmount = number_format((float) ($stats['grant_amount'] ?? 0), 2);
  $granteePercentage = $stats['grantee_percentage'] ?? 0;
  
  $studentsWithScholarship = $stats['students_with_scholarship'] ?? 0;
  $scholarRate = $totalStudents > 0 ? round(($studentsWithScholarship / $totalStudents) * 100, 1) : 0;
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="font-weight-bolder mb-0 text-primary-simple text-uppercase" style="letter-spacing: 1px;">Scholarship Dashboard</h4>
        <p class="text-sm text-secondary mb-0">Overview for <span id="current-ay-label">AY {{ $selectedAY ?: 'Full History' }}</span></p>
    </div>
    <div class="d-flex align-items-center position-relative">
        <div class="dropdown">
            <button class="btn bg-white shadow-sm border mb-0 dropdown-toggle text-uppercase text-xxs font-weight-bolder px-4" type="button" id="ayDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="opacity-7 me-2 border-end pe-2">Filter AY</span>
                <span id="selected-ay-display" style="color: #002d54;">{{ $selectedAY ?: 'Full History' }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2" aria-labelledby="ayDropdown" style="border-radius: 1rem; min-width: 200px;">
                <li>
                    <a class="dropdown-item border-radius-md py-2 ay-preview-item {{ !$selectedAY ? 'bg-gray-100 font-weight-bold' : '' }}" 
                       href="{{ route('dashboard') }}" 
                       data-ay="Full History">
                       Full History
                    </a>
                </li>
                <li><hr class="dropdown-divider opacity-1"></li>
                @foreach($academicYears as $ay)
                    <li>
                        <a class="dropdown-item border-radius-md py-2 ay-preview-item {{ $selectedAY == $ay ? 'bg-gray-100 font-weight-bold' : '' }}" 
                           href="{{ route('dashboard', ['ay' => $ay]) }}" 
                           data-ay="{{ $ay }}">
                           AY {{ $ay }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

<!-- KPI Card -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow-sm border-0 bg-white p-4" style="border-radius: 1.5rem;">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h6 class="text-uppercase text-secondary font-weight-bolder opacity-7 mb-1" style="letter-spacing: 1px;">Scholarship Enrollment Rate</h6>
                <h2 class="font-weight-bolder mb-0" id="kpi-scholar-rate" style="color: #002d54; font-size: 3rem;">
                    {{ $scholarRate }}%
                </h2>
                <p class="text-sm text-secondary mb-0">Percentage of registered students who are currently enrolled in a scholarship program.</p>
            </div>
            <div class="col-md-4 text-end d-none d-md-block">
                <div class="d-inline-flex position-relative align-items-center justify-content-center" style="width: 100px; height: 100px;">
                    <div class="position-absolute w-100 h-100" style="border: 8px solid #f8f9fa; border-radius: 50%;"></div>
                    <div class="position-absolute w-100 h-100" id="scholar-rate-ring" style="border: 8px solid #002d54; border-radius: 50%; clip-path: inset(0 0 {{ 100 - $scholarRate }}% 0); transition: all 0.5s ease;"></div>
                    <i class="fas fa-graduation-cap text-primary-simple opacity-3" style="font-size: 2rem;"></i>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-8 mb-lg-0 mb-4">
    <div class="card h-100 shadow-sm border-0 overflow-hidden">
      <div class="card-header pb-0 bg-white border-0">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 font-weight-bold">Scholars per Program</h6>
                <p class="text-xs text-secondary mb-0" id="chart-sub-label">Total scholarship distribution</p>
            </div>
            <div class="icon icon-shape bg-gray-100 shadow-none text-center border-radius-md">
                <i class="fas fa-chart-simple text-primary-simple opacity-10"></i>
            </div>
        </div>
      </div>
      <div class="card-body p-3">
        <div class="chart" style="height: 350px;">
          <canvas id="chart-bars" class="chart-canvas" height="350"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card h-100 shadow-sm border-0 overflow-hidden" style="background-color: #002d54 !important;">
      <div class="card-body p-3 d-flex flex-column position-relative" style="z-index: 1;">
        <div class="text-center mt-3">
            <h6 class="text-white text-uppercase font-weight-bold text-xxs opacity-7 mb-4" style="letter-spacing: 2px;">Overall Completion</h6>
            <div class="d-inline-flex position-relative align-items-center justify-content-center">
                <h1 class="display-3 font-weight-bolder mb-0" id="overall-percentage" style="color: #ffcc00 !important; text-shadow: 0 4px 10px rgba(0,0,0,0.3);">{{ $granteePercentage }}%</h1>
                <div class="position-absolute w-100 h-100" style="border: 4px solid rgba(255,204,0,0.15); border-radius: 50%; padding: 60px; scale: 1.5;"></div>
            </div>
            <p class="text-white text-xs mt-4 opacity-8 font-weight-bold" id="disbursement-label">Scholar Disbursement Progress</p>
        </div>

        <div class="mt-auto mb-3">
            <div class="bg-white p-3 rounded-4 shadow-sm mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-xxs font-weight-bolder text-uppercase text-secondary">Fund Utilization</span>
                    <span class="text-xs font-weight-bolder" id="amount-percentage-text" style="color: #002d54;">{{ $stats['amount_percentage'] }}%</span>
                </div>
                <div class="progress progress-xs mb-3" style="background-color: #e9ecef;">
                    <div class="progress-bar" id="amount-progress-bar" role="progressbar" aria-valuenow="{{ $stats['amount_percentage'] }}" aria-valuemin="0" aria-valuemax="100" style="background-color: #ffcc00 !important; width: {{ $stats['amount_percentage'] }}%;"></div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-xxs text-secondary mb-0 text-uppercase font-weight-bold">Released</p>
                        <h6 class="text-sm font-weight-bold mb-0" id="released-amount">₱{{ number_format($stats['grant_amount_disbursed'], 0) }}</h6>
                    </div>
                    <div class="text-end">
                        <p class="text-xxs text-secondary mb-0 text-uppercase font-weight-bold">Active Grant</p>
                        <h6 class="text-sm font-weight-bold mb-0" id="total-amount">₱{{ number_format($stats['grant_amount'], 0) }}</h6>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between px-2 text-white">
                <div class="text-center">
                    <p class="text-xxs opacity-7 text-uppercase mb-0 font-weight-bold">Scholars</p>
                    <h6 class="text-white mb-0" id="count-scholars">{{ number_format($stats['total_scholars_in_billing']) }}</h6>
                    <span class="text-xxs opacity-5">100%</span>
                </div>
                <div class="text-center">
                    <p class="text-xxs opacity-7 text-uppercase mb-0 font-weight-bold">Paid</p>
                    <h6 class="mb-0" id="count-paid" style="color: #ffcc00 !important;">{{ number_format($stats['total_disbursed_scholars']) }}</h6>
                    <span class="text-xxs font-weight-bold" id="perc-paid" style="color: #ffcc00 !important;">{{ $granteePercentage }}%</span>
                </div>
                <div class="text-center">
                    <p class="text-xxs opacity-7 text-uppercase mb-0 font-weight-bold">Pending</p>
                    <h6 class="text-white mb-0 opacity-8" id="count-pending">{{ number_format($stats['total_scholars_in_billing'] - $stats['total_disbursed_scholars']) }}</h6>
                    <span class="text-xxs opacity-5" id="perc-pending">{{ 100 - $granteePercentage }}%</span>
                </div>
            </div>
        </div>
      </div>
      <!-- Decorative element -->
      <div class="position-absolute top-0 end-0 opacity-1" style="transform: translate(20%, -20%); pointer-events: none;">
          <i class="fas fa-coins text-white opacity-1" style="font-size: 10rem;"></i>
      </div>
    </div>
  </div>
</div>

<script id="full-year-data" type="application/json">
    {!! json_encode($fullYearData) !!}
</script>
<script id="history-summary" type="application/json">
    {!! json_encode($historySummary) !!}
</script>

@push('js')
<script>
    var ctx = document.getElementById("chart-bars").getContext("2d");
    var fullYearData = JSON.parse(document.getElementById('full-year-data').textContent);
    var historySummary = JSON.parse(document.getElementById('history-summary').textContent);
    var currentAY = "{{ $selectedAY ?: 'Full History' }}";
    
    // Initial Data
    var initialChartLabels = {!! json_encode($chartData->pluck('program')) !!};
    var initialChartData = {!! json_encode($chartData->pluck('total')) !!};

    var chartInstance = new Chart(ctx, {
      type: "bar",
      data: {
        labels: initialChartLabels,
        datasets: [{
          label: "Scholars",
          tension: 0.4,
          borderWidth: 0,
          borderRadius: 4,
          borderSkipped: false,
          backgroundColor: "#002d54",
          data: initialChartData,
          maxBarThickness: 30
        }, ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
          duration: 500,
          easing: 'easeOutQuart'
        },
        plugins: {
          legend: {
            display: false,
          }
        },
        interaction: {
          intersect: false,
          mode: 'index',
        },
        scales: {
          y: {
            grid: {
              drawBorder: false,
              display: true,
              drawOnChartArea: true,
              drawTicks: false,
              borderDash: [5, 5]
            },
            ticks: {
              display: true,
              padding: 10,
              color: '#b2b9bf',
              font: {
                size: 11,
                family: "Open Sans",
                style: 'normal',
                lineHeight: 2
              },
            }
          },
          x: {
            grid: {
              drawBorder: false,
              display: false,
              drawOnChartArea: false,
              drawTicks: false,
              borderDash: [5, 5]
            },
            ticks: {
              display: true,
              color: '#b2b9bf',
              padding: 20,
              font: {
                size: 11,
                family: "Open Sans",
                style: 'normal',
                lineHeight: 2
              },
            }
          },
        },
      },
    });

    // Hover & Click Logic
    const previewItems = document.querySelectorAll('.ay-preview-item');
    let lockedAY = "{{ $selectedAY ?: 'Full History' }}";

    const updateDashboard = (data) => {
        // Update Overall %
        const percentageEl = document.getElementById('overall-percentage');
        if (percentageEl) percentageEl.innerText = data.granteePercentage + '%';
        
        // Update Fund % and Progress Bar
        const amtPercEl = document.getElementById('amount-percentage-text');
        if (amtPercEl) amtPercEl.innerText = data.amountPercentage + '%';
        
        const progressBarEl = document.getElementById('amount-progress-bar');
        if (progressBarEl) {
            progressBarEl.style.width = data.amountPercentage + '%';
            progressBarEl.setAttribute('aria-valuenow', data.amountPercentage);
        }
        
        // Update Amounts
        const releasedEl = document.getElementById('released-amount');
        if (releasedEl) releasedEl.innerText = '₱' + new Intl.NumberFormat().format(data.totalReleased);
        
        const totalAmtEl = document.getElementById('total-amount');
        if (totalAmtEl) totalAmtEl.innerText = '₱' + new Intl.NumberFormat().format(data.totalAmount);
        
        // Update Counts
        const scholarsEl = document.getElementById('count-scholars');
        if (scholarsEl) scholarsEl.innerText = new Intl.NumberFormat().format(data.totalScholars);
        
        const paidEl = document.getElementById('count-paid');
        if (paidEl) paidEl.innerText = new Intl.NumberFormat().format(data.totalPaid);
        
        const pendingEl = document.getElementById('count-pending');
        if (pendingEl) pendingEl.innerText = new Intl.NumberFormat().format(data.totalScholars - data.totalPaid);

        // Update Percentage Counts
        const percPaidEl = document.getElementById('perc-paid');
        if (percPaidEl) percPaidEl.innerText = data.granteePercentage + '%';
        
        const percPendingEl = document.getElementById('perc-pending');
        if (percPendingEl) percPendingEl.innerText = (100 - data.granteePercentage).toFixed(1) + '%';
        
        // Update Labels
        const labelEl = document.getElementById('current-ay-label');
        if (labelEl) labelEl.innerText = data.ay === 'Full History' ? 'Full History' : 'AY ' + data.ay;
        
        const displayEl = document.getElementById('selected-ay-display');
        if (displayEl) displayEl.innerText = data.ay === 'Full History' ? 'Full History' : 'AY ' + data.ay;

        // Update KPI Card
        // (Note: Scholar Enrollment Rate is a system-wide metric, but we'll keep the ID logic clean)
        const kpiRate = document.getElementById('kpi-scholar-rate');
        if (kpiRate && data.scholarRate) {
             kpiRate.innerText = data.scholarRate + '%';
        }
        
        const rateRing = document.getElementById('scholar-rate-ring');
        if (rateRing && data.scholarRate) {
            rateRing.style.clipPath = 'inset(0 0 ' + (100 - data.scholarRate) + '% 0)';
        }

        // Update Chart with smooth animation
        const labels = Object.keys(data.chart);
        const values = Object.values(data.chart);
        chartInstance.data.labels = labels;
        chartInstance.data.datasets[0].data = values;
        chartInstance.update();
    };

    previewItems.forEach(item => {
        item.addEventListener('mouseenter', () => {
            const ay = item.getAttribute('data-ay');
            const data = ay === 'Full History' ? historySummary : fullYearData[ay];
            if (data) updateDashboard(data);
        });

        item.addEventListener('mouseleave', () => {
            const data = lockedAY === 'Full History' ? historySummary : fullYearData[lockedAY];
            if (data) updateDashboard(data);
        });

        item.addEventListener('click', (e) => {
            e.preventDefault();
            const ay = item.getAttribute('data-ay');
            lockedAY = ay;
            
            previewItems.forEach(i => i.classList.remove('bg-gray-100', 'font-weight-bold'));
            item.classList.add('bg-gray-100', 'font-weight-bold');
            
            const url = item.getAttribute('href');
            window.history.pushState({ ay: ay }, '', url);
            
            const data = ay === 'Full History' ? historySummary : fullYearData[ay];
            if (data) updateDashboard(data);
            
            const dropdown = bootstrap.Dropdown.getInstance(document.getElementById('ayDropdown'));
            if (dropdown) dropdown.hide();
        });
    });

</script>
@endpush

@endsection
