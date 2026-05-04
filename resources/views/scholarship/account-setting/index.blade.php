@extends('layouts.user_type.auth')

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body d-md-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-1">Account Setting (Native)</h5>
          <p class="text-sm mb-0">Change your password using the native Laravel account session.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex flex-wrap">
          <a href="{{ route('scholarship-system') }}" class="btn btn-outline-dark mb-0 me-2">Back To Hub</a>
          <a href="{{ route('scholarship-system.module', 'account-setting') }}" class="btn btn-outline-primary mb-0">Legacy Page</a>
        </div>
      </div>
    </div>
  </div>
</div>

@if (session('success'))
  <div class="row">
    <div class="col-12">
      <div class="alert alert-success text-white" role="alert">{{ session('success') }}</div>
    </div>
  </div>
@endif

@if ($errors->any())
  <div class="row">
    <div class="col-12">
      <div class="alert alert-danger text-white" role="alert">
        <ul class="mb-0 ps-3">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>
@endif

<div class="row">
  <div class="col-lg-8 col-12">
    <div class="card">
      <div class="card-header pb-0">
        <h6 class="mb-0">Change Password</h6>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('scholarship-account-setting.update') }}" class="row g-3">
          @csrf

          <div class="col-md-8">
            <label class="form-label">Old Password</label>
            <input type="password" name="oldpassword" class="form-control" required />
          </div>

          <div class="col-md-8">
            <label class="form-label">New Password</label>
            <input type="password" name="newpassword" class="form-control" minlength="6" required />
          </div>

          <div class="col-md-8">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="newpassword_confirmation" class="form-control" minlength="6" required />
          </div>

          <div class="col-12">
            <button type="submit" class="btn bg-gradient-primary mb-0">Make Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4 col-12 mt-4 mt-lg-0">
    <div class="card">
      <div class="card-header pb-0">
        <h6 class="mb-0">Notes</h6>
      </div>
      <div class="card-body text-sm">
        <ul class="mb-0 ps-3">
          <li>Password must be at least 6 characters.</li>
          <li>Old password must match your current account password.</li>
          <li>Legacy MD5 hash compatibility is still supported during verification.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

@endsection
