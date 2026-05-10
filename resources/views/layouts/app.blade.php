<!--
=========================================================
* Soft UI Dashboard - v1.0.3
=========================================================

* Product Page: https://www.creative-tim.com/product/soft-ui-dashboard
* Copyright 2021 Creative Tim (https://www.creative-tim.com)
* Licensed under MIT (https://www.creative-tim.com/license)

* Coded by Creative Tim

=========================================================

* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
-->
<!DOCTYPE html>

@if (\Request::is('rtl'))
  <html dir="rtl" lang="ar">
@else
  <html lang="en" >
@endif

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  @if (env('IS_DEMO'))
      <x-demo-metas></x-demo-metas>
  @endif

  <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('assets/img/apple-icon.png') }}">
  <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/scholarship-icon.svg') }}">
  <title>
    SCHOLARSHIP MANAGEMENT SYSTEM
  </title>
  <!--     Fonts and icons     -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <!-- Nucleo Icons -->
  <link href="{{ asset('assets/css/nucleo-icons.css') }}" rel="stylesheet" />
  <link href="{{ asset('assets/css/nucleo-svg.css') }}" rel="stylesheet" />
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
  <!-- CSS Files -->
  <!-- CSS Files -->
  <link id="pagestyle" href="{{ asset('assets/css/soft-ui-dashboard.css') }}?v=1.0.3" rel="stylesheet" />
  <link href="{{ asset('assets/css/professional-office.css') }}" rel="stylesheet" />
  @stack('css')
  <style>
    body {
      font-family: 'Inter', 'Open Sans', sans-serif;
      overflow-x: hidden;
    }

    /* Animated Background */
    body::before {
      content: "";
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -1;
      background: #f8fafc;
    }

    @keyframes bgPulse {
      0% { background-position: 0% 0%; }
      100% { background-position: 100% 100%; }
    }

    /* Card Fade-In Animation */
    .card {
      animation: fadeInSlide 0.6s ease-out forwards;
      opacity: 0;
    }

    @keyframes fadeInSlide {
      from {
        opacity: 0;
        transform: translateY(15px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Modern UI Enhancements */
    /* Modern UI Enhancements */
    :root {
      --glass-bg: rgba(255, 255, 255, 0.95);
      --glass-border: rgba(0, 0, 0, 0.05);
      --primary-gradient: linear-gradient(310deg, #003366 0%, #0066cc 100%);
      --bs-primary: #003366 !important;
    }

    /* Glassmorphism for Cards and Sidenav */
    .card, .sidenav {
      background: var(--glass-bg) !important;
      backdrop-filter: blur(12px) saturate(160%) !important;
      -webkit-backdrop-filter: blur(12px) saturate(160%) !important;
      border: 1px solid var(--glass-border) !important;
      box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07) !important;
    }

    .navbar-vertical .navbar-nav > .nav-item .nav-link.active {
      background-color: #fff !important;
      box-shadow: 0 20px 27px 0 rgba(0, 0, 0, 0.05) !important;
      border-radius: 0.75rem;
      margin: 0 1rem;
    }

    /* Keep sidebar active accents aligned with scholarship branding. */
    .sidenav[data-color="primary"] .navbar-nav > .nav-item > .nav-link.active .icon,
    .navbar-vertical .navbar-nav > .nav-item .nav-link.active .icon {
      background-image: var(--primary-gradient) !important;
      box-shadow: 0 4px 6px -1px rgba(33, 82, 255, 0.3), 0 2px 4px -1px rgba(33, 82, 255, 0.1) !important;
    }

    /* Modern Table Row Hover */
    .table tbody tr {
      transition: all 0.3s ease;
    }
    .table tbody tr:hover {
      background-color: rgba(33, 82, 255, 0.04) !important;
      transform: translateX(4px);
    }

    /* Card Hover Effects */
    .card {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.15) !important;
    }

    /* Icon Hover Animation */
    .icon i, .nav-link i {
      transition: all 0.3s ease;
    }
    .nav-link:hover i {
      transform: scale(1.2) rotate(5deg);
      color: #2152ff !important;
    }

    /* Custom Modern Scrollbar */
    ::-webkit-scrollbar {
      width: 5px;
      height: 5px;
    }
    ::-webkit-scrollbar-track {
      background: transparent;
    }
    ::-webkit-scrollbar-thumb {
      background: rgba(0, 0, 0, 0.1);
      border-radius: 10px;
      transition: background 0.3s;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: rgba(0, 0, 0, 0.2);
    }

    /* Refined Button Effects */
    .btn {
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
      text-transform: none !important;
      letter-spacing: 0 !important;
      font-weight: 600 !important;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08) !important;
    }

    .btn:active {
      transform: translateY(0);
    }

    /* Reusable checkbox with visible check icon */
    .check-icon-input {
      appearance: none;
      -webkit-appearance: none;
      width: 1.15rem;
      height: 1.15rem;
      border: 1px solid #d2d6da;
      border-radius: 0.25rem;
      background-color: #fff;
      background-position: center;
      background-repeat: no-repeat;
      background-size: 0.75rem 0.75rem;
      cursor: pointer;
      vertical-align: middle;
      transition: all 0.2s;
    }

    .check-icon-input:checked {
      border-color: #2152ff;
      background-color: #2152ff;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 8.5l3 3L13 4.5'/%3E%3C/svg%3E");
    }

    /* Hover feedback for interactive feature controls. */
    .hover-fx-target {
      transition: all 0.2s ease;
    }

    .hover-fx-target:hover {
      transform: translateY(-1px);
    }

    /* Click feedback */
    .click-fx-target {
      position: relative;
      overflow: hidden;
      transition: transform 0.1s ease;
    }

    .click-fx-target:active {
      transform: scale(0.97);
    }

    .click-fx-ripple {
      position: absolute;
      background: rgba(33, 82, 255, 0.15);
      border-radius: 50%;
      transform: scale(0);
      animation: ripple-animation 0.6s linear;
      pointer-events: none;
    }

    @keyframes ripple-animation {
      to {
        transform: scale(4);
        opacity: 0;
      }
    }
    .btn-primary-simple {
      background-color: #002d54 !important;
      color: #fff !important;
      border: none !important;
    }
    .btn-outline-simple {
      border: 1px solid #002d54 !important;
      color: #002d54 !important;
      background: transparent !important;
    }
    .btn-icon-only {
      width: 38px;
      height: 38px;
      padding: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 12px !important;
      transition: all 0.3s ease !important;
    }
    .btn-icon-only.btn-sm {
      width: 32px;
      height: 32px;
      border-radius: 10px !important;
    }
    .btn-icon-only.btn-xs {
      width: 26px;
      height: 26px;
      border-radius: 8px !important;
      font-size: 0.7rem;
    }
    .btn-group-icons {
      display: flex;
      gap: 10px;
      align-items: center;
    }
    .bg-primary-simple {
      background-color: #002d54 !important;
      color: #fff !important;
    }
    .text-primary-simple {
      color: #002d54 !important;
    }
    .bg-outline-simple {
      background-color: transparent !important;
      border: 1px solid #002d54 !important;
    }
  </style>
</head>

<body class="g-sidenav-show  bg-gray-100 {{ (\Request::is('rtl') ? 'rtl' : (Request::is('virtual-reality') ? 'virtual-reality' : '')) }} ">
  @auth
    @yield('auth')
  @endauth
  @guest
    @yield('guest')
  @endguest

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    /* Premium Glass Toast Styling */
    .swal2-toast {
      background: rgba(255, 255, 255, 0.85) !important;
      backdrop-filter: blur(10px) saturate(180%) !important;
      -webkit-backdrop-filter: blur(10px) saturate(180%) !important;
      border: 1px solid rgba(255, 255, 255, 0.5) !important;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
      border-radius: 1rem !important;
    }
    .swal2-toast .swal2-title {
      color: #002d54 !important;
      font-weight: 700 !important;
      font-size: 0.9rem !important;
    }
    .swal2-toast .swal2-html-container {
      color: #344767 !important;
      font-size: 0.8rem !important;
      max-height: 250px;
      overflow-y: auto;
      padding-right: 5px;
    }
    /* Scrollbar for long conflict lists */
    .swal2-html-container::-webkit-scrollbar { width: 3px; }
    .swal2-html-container::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 10px; }
    
    .swal2-popup {
      font-family: 'Inter', sans-serif !important;
      border-radius: 1.25rem !important;
      box-shadow: 0 20px 27px 0 rgba(0, 0, 0, 0.1) !important;
    }
    .swal2-styled.swal2-confirm {
      background-image: var(--primary-gradient) !important;
      border-radius: 0.5rem !important;
      font-weight: 600 !important;
    }
  </style>

  <script>
    // Modern Alert System Helpers
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
      }
    });

    window.showToast = (icon, title) => {
      Toast.fire({ icon, title });
    };

    window.confirmAction = (event, title, text) => {
      event.preventDefault();
      const form = event.target.closest('form');
      showConfirm(title, text).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    };

    window.showConfirm = (title, text, confirmButtonText = 'Yes, proceed') => {
      return Swal.fire({
        title,
        text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        customClass: {
          confirmButton: 'btn bg-gradient-primary ms-2',
          cancelButton: 'btn btn-outline-secondary'
        },
        buttonsStyling: false
      });
    };

    @if(session()->has('success'))
      window.addEventListener('DOMContentLoaded', () => {
        showToast('success', '{{ session('success') }}');
      });
    @endif

    @if(session()->has('error') || $errors->any())
      window.addEventListener('DOMContentLoaded', () => {
        @if($errors->any())
          showToast('error', 'Please check the form for errors.');
        @else
          showToast('error', '{{ session('error') }}');
        @endif
      });
    @endif
  </script>
    <!--   Core JS Files   -->
  <script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
  <script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
  <script src="{{ asset('assets/js/plugins/perfect-scrollbar.min.js') }}"></script>
  <script src="{{ asset('assets/js/plugins/smooth-scrollbar.min.js') }}"></script>
  <script src="{{ asset('assets/js/plugins/fullcalendar.min.js') }}"></script>
  <script src="{{ asset('assets/js/plugins/chartjs.min.js') }}"></script>
  @stack('rtl')
  @stack('dashboard')
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }

    (function () {
      var selector = 'a.nav-link, .btn, [data-click-effect]';
      var darkClasses = [
        'bg-gradient-dark',
        'bg-gradient-primary',
        'bg-gradient-info',
        'bg-gradient-danger',
        'bg-gradient-success',
        'btn-dark'
      ];

      function isDarkTarget(target) {
        for (var i = 0; i < darkClasses.length; i++) {
          if (target.classList.contains(darkClasses[i])) {
            return true;
          }
        }

        return false;
      }

      function createRipple(target, clientX, clientY) {
        target.classList.add('click-fx-target');
        target.classList.toggle('click-fx-on-dark', isDarkTarget(target));

        var rect = target.getBoundingClientRect();
        var x = clientX;
        var y = clientY;

        if (typeof x !== 'number' || typeof y !== 'number') {
          x = rect.left + (rect.width / 2);
          y = rect.top + (rect.height / 2);
        }

        var ripple = document.createElement('span');
        ripple.className = 'click-fx-ripple';
        ripple.style.left = (x - rect.left) + 'px';
        ripple.style.top = (y - rect.top) + 'px';
        target.appendChild(ripple);

        ripple.addEventListener('animationend', function () {
          ripple.remove();
        }, { once: true });

        target.classList.add('click-fx-press');
        window.setTimeout(function () {
          target.classList.remove('click-fx-press');
        }, 140);
      }

      document.addEventListener('pointerdown', function (event) {
        if (event.button !== 0) {
          return;
        }

        var target = event.target.closest(selector);
        if (!target) {
          return;
        }

        if (target.matches('[disabled], .disabled, [aria-disabled="true"]')) {
          return;
        }

        createRipple(target, event.clientX, event.clientY);
      }, true);

      document.querySelectorAll(selector).forEach(function (el) {
        el.classList.add('hover-fx-target');
      });

      document.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') {
          return;
        }

        var target = event.target.closest(selector);
        if (!target) {
          return;
        }

        createRipple(target);
      }, true);
    })();
  </script>
  <!-- Github buttons -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- Control Center for Soft Dashboard: parallax effects, scripts for the example pages etc -->
  <script src="{{ asset('assets/js/soft-ui-dashboard.min.js') }}?v=1.0.3"></script>
  <script>
    // Clear form persistence on logout
    document.addEventListener('click', function(e) {
      const logoutLink = e.target.closest('a[href*="logout"]');
      if (logoutLink) {
        Object.keys(localStorage).forEach(key => {
          if (key.startsWith('scholarship_student_form_')) {
            localStorage.removeItem(key);
          }
        });
      }
    });
  </script>

  <!-- Global Toast Handler -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
        didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer)
          toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
      });

      @if (session('success'))
        Toast.fire({ icon: 'success', html: "{!! session('success') !!}" });
      @endif

      @if (session('error'))
        Toast.fire({ icon: 'error', html: "{!! session('error') !!}" });
      @endif

      @if (session('warning'))
        Toast.fire({ icon: 'warning', html: "{!! session('warning') !!}" });
      @endif

      @if (session('status'))
        Toast.fire({ icon: 'info', html: "{!! session('status') !!}" });
      @endif

      @if ($errors->any())
        Toast.fire({ 
          icon: 'error', 
          title: 'Validation Error',
          text: "{{ $errors->first() }}"
        });
      @endif
    });
  </script>
  @stack('js')
</body>

</html>
