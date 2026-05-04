@extends('layouts.user_type.guest')

@section('content')

  <style>
    /* Hide top navbar to keep focus on branding */
    .navbar.navbar-expand-lg.position-absolute.top-0 {
      display: none !important;
    }

    .login-container {
      position: relative;
      min-height: 100vh;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      background-image: url('{{ asset('assets/img/norsu_bg.png') }}');
      background-size: cover;
      background-position: center;
      padding: 20px;
      overflow: hidden;
    }

    /* Subtle overlay for better readability */
    .login-container::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(0, 51, 102, 0.7) 0%, rgba(0, 102, 204, 0.4) 100%);
      z-index: 1;
    }

    .login-glass-card {
      position: relative;
      z-index: 2;
      width: 100%;
      max-width: 450px;
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 24px;
      padding: 40px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
      animation: cardFadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes cardFadeUp {
      from { opacity: 0; transform: translateY(40px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .brand-section {
      text-align: center;
      margin-bottom: 32px;
    }

    .brand-logo {
      width: 100px;
      height: 100px;
      margin-bottom: 16px;
      filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
      transition: transform 0.5s ease;
    }

    .brand-logo:hover {
      transform: rotate(5deg) scale(1.05);
    }

    .portal-title {
      color: #003366;
      font-weight: 800;
      font-size: 1.75rem;
      letter-spacing: -0.5px;
      margin-bottom: 4px;
    }

    .portal-subtitle {
      color: #64748b;
      font-size: 0.95rem;
    }

    .form-label {
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 8px;
      font-size: 0.875rem;
    }

    .modern-input {
      background: #f8fafc !important;
      border: 2px solid transparent !important;
      border-radius: 12px !important;
      padding: 12px 16px !important;
      transition: all 0.2s ease !important;
      font-weight: 500 !important;
    }

    .modern-input:focus {
      border-color: #003366 !important;
      background: #fff !important;
      box-shadow: 0 0 0 4px rgba(0, 51, 102, 0.1) !important;
    }

    .signin-btn {
      background: linear-gradient(310deg, #003366 0%, #0066cc 100%) !important;
      border: none !important;
      border-radius: 12px !important;
      padding: 14px !important;
      font-weight: 700 !important;
      font-size: 1rem !important;
      color: #fff !important;
      margin-top: 24px !important;
      box-shadow: 0 4px 12px rgba(0, 51, 102, 0.3) !important;
    }

    .signin-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0, 51, 102, 0.4) !important;
    }

    .footer-links {
      text-align: center;
      margin-top: 24px;
      border-top: 1px solid #e2e8f0;
      padding-top: 20px;
    }

    .footer-link {
      color: #003366;
      font-weight: 600;
      text-decoration: none;
      transition: color 0.2s;
    }

    .footer-link:hover {
      color: #0066cc;
      text-decoration: underline;
    }

    .system-status {
      position: absolute;
      bottom: 20px;
      left: 20px;
      color: #fff;
      font-size: 0.75rem;
      z-index: 2;
      opacity: 0.8;
      font-weight: 500;
    }
  </style>

  <main class="main-content mt-0">
    <div class="login-container">
      <div class="login-glass-card">
        <div class="brand-section">
          <img src="https://norsu.edu.ph/wp-content/uploads/2021/05/NORSU-Logo.png" alt="NORSU Logo" class="brand-logo">
          <h1 class="portal-title">NORSU</h1>
          <p class="portal-subtitle">Scholarship Management System</p>
        </div>

        <form role="form" method="POST" action="/session">
          @csrf
          <div class="mb-4">
            <label class="form-label">Administrative Email</label>
            <input type="email" class="form-control modern-input" name="email" id="email" placeholder="email@norsu.edu.ph" value="admin@softui.com" required>
            @error('email')
              <p class="text-danger text-xs mt-2">{{ $message }}</p>
            @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control modern-input" name="password" id="password" placeholder="••••••••" value="secret" required>
            @error('password')
              <p class="text-danger text-xs mt-2">{{ $message }}</p>
            @enderror
          </div>

          <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" id="rememberMe" checked="">
              <label class="form-check-label text-sm mb-0" for="rememberMe">Remember session</label>
            </div>
            <a href="/login/forgot-password" class="text-sm footer-link">Forgot password?</a>
          </div>

          <button type="submit" class="btn signin-btn w-100">Access Portal</button>
        </form>

        <div class="footer-links">
          <p class="text-sm text-muted">
            New administrator? 
            <a href="register" class="footer-link">Request Access</a>
          </p>
        </div>
      </div>

      <div class="system-status">
        <i class="fas fa-shield-halved me-1"></i> Secure Administrative Gateway | Negros Oriental State University
      </div>
    </div>
  </main>

@endsection
