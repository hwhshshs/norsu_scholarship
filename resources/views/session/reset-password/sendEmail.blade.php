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

    .footer-link {
      color: #003366;
      font-weight: 600;
      text-decoration: none;
    }
  </style>

  <div class="reset-container">
    <div class="reset-glass-card">
      <div class="text-center mb-4">
        <img src="{{ asset('assets/img/norsu_logo.png') }}" alt="NORSU Logo" class="brand-logo">
        <h1 class="portal-title">Account Recovery</h1>
        <p class="text-muted text-sm">Enter your email to receive a password reset link.</p>
      </div>

      @if (session('status'))
        <div class="alert alert-success text-white border-radius-lg" role="alert">
          {{ session('status') }}
        </div>
      @endif

      <form action="/forgot-password" method="POST" role="form text-left">
        @csrf
        <div class="mb-4">
          <label class="form-label">Registered Email</label>
          <input type="email" class="form-control modern-input" placeholder="admin@norsu.edu.ph" name="email" id="email" aria-label="Email" aria-describedby="email-addon" required>
          @error('email')
            <p class="text-danger text-xs mt-2">{{ $message }}</p>
          @enderror
        </div>
        
        <button type="submit" class="btn action-btn w-100">Send Recovery Link</button>
        
        <div class="text-center mt-4">
          <a href="/login" class="text-sm footer-link"><i class="fas fa-arrow-left me-1"></i> Back to login</a>
        </div>
      </form>
    </div>
  </div>

@endsection