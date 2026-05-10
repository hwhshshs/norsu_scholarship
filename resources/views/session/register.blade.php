@extends('layouts.user_type.guest')

@section('content')

  <style>
    /* Hide top navbar to keep focus on branding */
    .navbar.navbar-expand-lg.position-absolute.top-0 {
      display: none !important;
    }

    .register-container {
      position: relative;
      min-height: 100vh;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      background-image: url('{{ asset('assets/img/norsu_bg.png') }}');
      background-size: cover;
      background-position: center;
      padding: 40px 20px;
      overflow-y: auto;
    }

    .register-container::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(0, 51, 102, 0.7) 0%, rgba(0, 102, 204, 0.4) 100%);
      z-index: 1;
    }

    .register-glass-card {
      position: relative;
      z-index: 2;
      width: 100%;
      max-width: 500px;
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
      width: 80px;
      height: 80px;
      margin-bottom: 12px;
      filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
    }

    .portal-title {
      color: #003366;
      font-weight: 800;
      font-size: 1.5rem;
      letter-spacing: -0.5px;
      margin-bottom: 4px;
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

    .action-btn {
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

    .action-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0, 51, 102, 0.4) !important;
    }

    .footer-link {
      color: #003366;
      font-weight: 600;
      text-decoration: none;
    }

    .footer-link:hover {
      text-decoration: underline;
    }
  </style>

  <main class="main-content mt-0">
    <div class="register-container">
      <div class="register-glass-card">
        <div class="brand-section">
          <img src="{{ asset('assets/img/norsu_logo.png') }}" alt="NORSU Logo" class="brand-logo">
          <h1 class="portal-title">Create Administrator Account</h1>
          <p class="text-muted text-sm">Access the Scholarship Management System</p>
        </div>

        <form role="form" method="POST" action="/register">
          @csrf
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control modern-input" placeholder="Juan Dela Cruz" name="name" id="name" aria-label="Name" value="{{ old('name') }}" required>
            @error('name')
              <p class="text-danger text-xs mt-2">{{ $message }}</p>
            @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Official Email</label>
            <input type="email" class="form-control modern-input" placeholder="admin@norsu.edu.ph" name="email" id="email" aria-label="Email" value="{{ old('email') }}" required>
            @error('email')
              <p class="text-danger text-xs mt-2">{{ $message }}</p>
            @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control modern-input" placeholder="••••••••" name="password" id="password" aria-label="Password" required>
            @error('password')
              <p class="text-danger text-xs mt-2">{{ $message }}</p>
            @enderror
          </div>

          <div class="form-check form-check-info text-left mb-4">
            <input class="form-check-input" type="checkbox" name="agreement" id="flexCheckDefault" checked>
            <label class="form-check-label text-sm" for="flexCheckDefault">
              I agree to the <a href="javascript:;" class="footer-link">NORSU Data Privacy Terms</a>
            </label>
            @error('agreement')
              <p class="text-danger text-xs mt-2">Please accept the terms to continue.</p>
            @enderror
          </div>

          <button type="submit" class="btn action-btn w-100">Register Account</button>
          
          <p class="text-sm mt-4 text-center mb-0">
            Already have an account? 
            <a href="login" class="footer-link">Sign in instead</a>
          </p>
        </form>
      </div>
    </div>
  </main>

@endsection
