@extends('layouts.user_type.guest')

@section('content')

  <style>
    .navbar.navbar-expand-lg.position-absolute.top-0 {
      display: none !important;
    }

    .reset-container {
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
    }

    .reset-container::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(0, 51, 102, 0.7) 0%, rgba(0, 102, 204, 0.4) 100%);
      z-index: 1;
    }

    .reset-glass-card {
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
    }

    .brand-logo {
      width: 70px;
      height: 70px;
      margin-bottom: 16px;
    }

    .portal-title {
      color: #003366;
      font-weight: 800;
      font-size: 1.5rem;
      margin-bottom: 8px;
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
    }

    .modern-input:focus {
      border-color: #003366 !important;
      background: #fff !important;
    }

    .action-btn {
      background: linear-gradient(310deg, #003366 0%, #0066cc 100%) !important;
      border: none !important;
      border-radius: 12px !important;
      padding: 14px !important;
      font-weight: 700 !important;
      color: #fff !important;
      margin-top: 16px !important;
    }
  </style>

  <div class="reset-container">
    <div class="reset-glass-card">
      <div class="text-center mb-4">
        <img src="https://norsu.edu.ph/wp-content/uploads/2021/05/NORSU-Logo.png" alt="NORSU Logo" class="brand-logo">
        <h1 class="portal-title">New Credentials</h1>
        <p class="text-muted text-sm">Secure your account with a new password.</p>
      </div>

      <form action="/reset-password" method="POST" role="form text-left">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        
        <div class="mb-3">
          <label class="form-label">Email Address</label>
          <input type="email" class="form-control modern-input" placeholder="admin@norsu.edu.ph" name="email" id="email" aria-label="Email" aria-describedby="email-addon" required>
          @error('email')
            <p class="text-danger text-xs mt-2">{{ $message }}</p>
          @enderror
        </div>

        <div class="mb-3">
          <label class="form-label">New Password</label>
          <input type="password" class="form-control modern-input" placeholder="••••••••" name="password" id="password" aria-label="Password" aria-describedby="password-addon" required>
          @error('password')
            <p class="text-danger text-xs mt-2">{{ $message }}</p>
          @enderror
        </div>

        <div class="mb-4">
          <label class="form-label">Confirm New Password</label>
          <input type="password" class="form-control modern-input" placeholder="••••••••" name="password_confirmation" id="password-confirm" aria-label="Password" aria-describedby="password-addon" required>
        </div>
        
        <button type="submit" class="btn action-btn w-100">Update Password</button>
      </form>
    </div>
  </div>

@endsection