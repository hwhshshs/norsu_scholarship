@extends('layouts.user_type.auth')

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body d-md-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-1">Scholarship Integration Checklist</h5>
          <p class="text-sm mb-0">Use this page to validate auto-login bridge, module launch, and synchronized logout behavior.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex">
          <a href="{{ route('scholarship-system') }}" class="btn btn-outline-dark mb-0 me-2">Back To Hub</a>
          <a href="{{ route('dashboard') }}" class="btn bg-gradient-primary mb-0">Dashboard</a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-8 mb-lg-0 mb-4">
    <div class="card h-100">
      <div class="card-header pb-0">
        <h6 class="mb-0">System Checks</h6>
      </div>
      <div class="card-body pt-3">
        <ul class="list-group">
          @foreach ($checks as $check)
            <li class="list-group-item border-0 px-0">
              <div class="d-flex justify-content-between align-items-start">
                <div class="pe-2">
                  <h6 class="text-sm mb-1">{{ $check['name'] }}</h6>
                  <p class="text-xs text-secondary mb-0">{{ $check['hint'] }}</p>
                </div>
                <span class="badge {{ $check['ok'] ? 'bg-gradient-success' : 'bg-gradient-danger' }}">{{ $check['ok'] ? 'PASS' : 'CHECK' }}</span>
              </div>
            </li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-header pb-0">
        <h6 class="mb-0">Quick Actions</h6>
      </div>
      <div class="card-body pt-3">
        @foreach ($quickActions as $action)
          <a href="{{ $action['url'] }}" class="btn btn-sm {{ $action['class'] }} w-100 mb-2">{{ $action['label'] }}</a>
        @endforeach
      </div>
    </div>

    <div class="card">
      <div class="card-header pb-0">
        <h6 class="mb-0">Bridge Endpoints</h6>
      </div>
      <div class="card-body pt-3">
        <p class="text-xs text-secondary mb-1">Bridge Consume Endpoint</p>
        <p class="text-xs mb-3" style="word-break: break-all;">{{ $bridgeConsumeUrl }}?token=YOUR_TOKEN</p>

        <p class="text-xs text-secondary mb-1">Sample Launch URL</p>
        <p class="text-xs mb-0" style="word-break: break-all;">{{ $sampleLaunchUrl }}</p>
      </div>
    </div>
  </div>
</div>

@endsection
