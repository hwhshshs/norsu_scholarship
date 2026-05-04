@extends('layouts.user_type.auth')

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body d-md-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-1">{{ $module['name'] }}</h5>
          <p class="text-sm mb-0">{{ $module['description'] }}</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex">
          <a href="{{ route('scholarship-system') }}" class="btn btn-outline-primary mb-0 me-2">All Modules</a>
          <a href="{{ $launchUrl }}" target="_blank" rel="noopener" class="btn bg-gradient-primary mb-0">Open In New Tab</a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header pb-0">
        <h6 class="mb-0">Embedded Legacy Module</h6>
      </div>
      <div class="card-body p-0 overflow-hidden">
        <iframe id="legacy-module-frame" src="{{ $launchUrl }}" title="{{ $module['name'] }}" style="width: 100%; min-height: 1100px; border: 0;"></iframe>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header pb-0">
        <h6 class="mb-0">Quick Module Switch</h6>
      </div>
      <div class="card-body pt-3">
        @foreach ($moduleGroups as $groupName => $items)
          <p class="text-xs text-uppercase text-secondary fw-bold mb-2">{{ $groupName }}</p>
          <div class="d-flex flex-wrap mb-3">
            @foreach ($items as $item)
              <a href="{{ route('scholarship-system.module', $item['slug']) }}" class="btn btn-sm {{ $module['slug'] === $item['slug'] ? 'bg-gradient-dark text-white' : 'btn-outline-dark' }} mb-2 me-2">{{ $item['name'] }}</a>
            @endforeach
          </div>
        @endforeach
      </div>
    </div>
  </div>
</div>

@endsection

@push('dashboard')
<script>
  (function () {
    var frame = document.getElementById('legacy-module-frame');
    if (!frame) {
      return;
    }

    function resizeAndBlendLegacyPage() {
      try {
        var doc = frame.contentDocument || frame.contentWindow.document;
        if (!doc || !doc.head) {
          return;
        }

        var styleId = 'legacy-embedded-style';
        if (!doc.getElementById(styleId)) {
          var style = doc.createElement('style');
          style.id = styleId;
          style.textContent = [
            'nav.navbar-cls-top, nav.navbar-side { display: none !important; }',
            '#page-wrapper { margin: 0 !important; }',
            '#page-inner { padding-top: 20px !important; }',
            '#wrapper { background: #fff !important; }'
          ].join(' ');
          doc.head.appendChild(style);
        }

        var target = doc.getElementById('page-inner') || doc.body;
        var contentHeight = target ? target.scrollHeight : 1100;
        frame.style.height = Math.max(contentHeight + 80, 1100) + 'px';
      } catch (e) {
        frame.style.height = '1300px';
      }
    }

    frame.addEventListener('load', resizeAndBlendLegacyPage);
    window.addEventListener('resize', resizeAndBlendLegacyPage);
  })();
</script>
@endpush
