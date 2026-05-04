@extends('layouts.user_type.auth')

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
        <div class="d-flex align-items-center">
          <a href="{{ url()->previous() }}" class="btn btn-icon-only btn-rounded btn-outline-secondary mb-0 me-3">
            <i class="fas fa-arrow-left"></i>
          </a>
          <div>
            <h5 class="mb-1"><i class="fas fa-chart-line me-1 text-primary"></i> Monthly Billing Summary</h5>
            <p class="text-sm mb-0">Overview of scholars billed per month across different programs.</p>
          </div>
        </div>
    </div>
  </div>
</div>

<div class="row">
  @forelse ($summary as $index => $prog)
    <div class="col-xl-6 mb-4">
      <div class="card h-100">
        <div class="card-header pb-0 p-3">
          <div class="d-flex justify-content-between">
            <h6 class="mb-0">{{ $prog['program'] }}</h6>
            <span class="badge bg-gradient-primary">{{ number_format($prog['total_scholars']) }} Total Scholars</span>
          </div>
        </div>
        <div class="card-body p-3">
          <!-- Chart Container -->
          <div class="chart mb-4">
            <canvas id="chart-{{ $index }}" class="chart-canvas" height="150"></canvas>
          </div>

          <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3">Monthly Breakdown</h6>
          <div class="table-responsive">
            <table class="table align-items-center mb-0">
              <thead>
                <tr>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Month</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Batches</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Scholars</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-end pe-2">Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($prog['months'] as $m)
                  <tr>
                    <td>
                      <p class="text-sm font-weight-bold mb-0">{{ $m->month_label }}</p>
                    </td>
                    <td class="text-center">
                      <span class="text-sm">{{ $m->batch_count }}</span>
                    </td>
                    <td class="text-center">
                      <span class="text-sm font-weight-bold">{{ number_format($m->total_scholars) }}</span>
                    </td>
                    <td class="text-end">
                      <a href="{{ route('scholarship-billing.index', ['program' => $prog['program'], 'month' => $m->month_key]) }}" class="btn btn-link text-primary text-gradient px-3 mb-0">
                        <i class="fas fa-search me-1"></i> View
                      </a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  @empty
    <div class="col-12">
      <div class="card">
        <div class="card-body text-center py-5">
          <p class="text-secondary mb-0">No billing data available to generate a summary.</p>
        </div>
      </div>
    </div>
  @endforelse
</div>

@endsection

@push('dashboard')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    @foreach ($summary as $index => $prog)
      @php
        $labels = [];
        $data = [];
        // Reverse for chronological order in chart
        foreach (array_reverse($prog['months']) as $m) {
          $labels[] = $m->month_label;
          $data[] = (int) $m->total_scholars;
        }
      @endphp

      var ctx = document.getElementById("chart-{{ $index }}").getContext("2d");

      new Chart(ctx, {
        type: "bar",
        data: {
          labels: {!! json_encode($labels) !!},
          datasets: [{
            label: "Scholars",
            tension: 0.4,
            borderWidth: 0,
            borderRadius: 4,
            borderSkipped: false,
            backgroundColor: "#fff",
            data: {!! json_encode($data) !!},
            maxBarThickness: 10
          }, ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
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
                display: false,
                drawOnChartArea: false,
                drawTicks: false,
              },
              ticks: {
                suggestedMin: 0,
                suggestedMax: 500,
                beginAtZero: true,
                padding: 15,
                font: {
                  size: 14,
                  family: "Open Sans",
                  style: 'normal',
                  lineHeight: 2
                },
                color: "#fff"
              },
            },
            x: {
              grid: {
                drawBorder: false,
                display: false,
                drawOnChartArea: false,
                drawTicks: false
              },
              ticks: {
                display: false
              },
            },
          },
        },
      });

      // Style the parent card background for the chart
      document.getElementById("chart-{{ $index }}").parentElement.style.background = "linear-gradient(310deg, #2152ff, #21d4fd)";
      document.getElementById("chart-{{ $index }}").parentElement.classList.add("border-radius-lg", "py-3", "pe-1");
    @endforeach
  });
</script>
@endpush
