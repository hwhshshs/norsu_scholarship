@extends('layouts.user_type.auth')


@section('content')

@php
  $yearsTotal = (int) ($stats['years_total'] ?? 0);
  $yearsActive = (int) ($stats['years_active'] ?? 0);
  $semestersTotal = (int) ($stats['semesters_total'] ?? 0);
  $semestersActive = (int) ($stats['semesters_active'] ?? 0);
  $levelsTotal = (int) ($stats['year_levels_total'] ?? 0);
  $levelsActive = (int) ($stats['year_levels_active'] ?? 0);
  $programsTotal = (int) ($stats['programs_total'] ?? 0);
  $programsActive = (int) ($stats['programs_active'] ?? 0);
@endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body d-md-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-1">Academic Management (Native)</h5>
          <p class="text-sm mb-0">Manage academic years, semesters, year levels, and scholarship programs in Laravel while preserving your template UI.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex flex-wrap">
          <a href="{{ route('scholarship-system') }}" class="btn btn-outline-dark mb-0 me-2">Back To Hub</a>
          <a href="{{ route('scholarship-system.module', 'academic-management') }}" class="btn btn-outline-primary mb-0">Legacy Page</a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-3 mb-4">
    <div class="card h-100">
      <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-center mb-3">
          <div class="icon icon-shape icon-sm bg-gradient-primary shadow text-center border-radius-md me-2">
            <i class="fas fa-calendar text-white"></i>
          </div>
          <h6 class="mb-0">Academic Years</h6>
        </div>
        <p class="text-sm mb-2">Total: <strong>{{ number_format($yearsTotal) }}</strong></p>
        <p class="text-sm mb-3">Active: <strong>{{ number_format($yearsActive) }}</strong></p>
        <a href="{{ route('scholarship-academic.years.index') }}" class="btn btn-sm bg-gradient-primary mt-auto mb-0">Open Academic Years</a>
      </div>
    </div>
  </div>

  <div class="col-lg-3 mb-4">
    <div class="card h-100">
      <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-center mb-3">
          <div class="icon icon-shape icon-sm bg-gradient-info shadow text-center border-radius-md me-2">
            <i class="fas fa-clock text-white"></i>
          </div>
          <h6 class="mb-0">Semesters</h6>
        </div>
        <p class="text-sm mb-2">Total: <strong>{{ number_format($semestersTotal) }}</strong></p>
        <p class="text-sm mb-3">Active: <strong>{{ number_format($semestersActive) }}</strong></p>
        <a href="{{ route('scholarship-academic.semesters.index') }}" class="btn btn-sm bg-gradient-info mt-auto mb-0">Open Semesters</a>
      </div>
    </div>
  </div>

  <div class="col-lg-3 mb-4">
    <div class="card h-100">
      <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-center mb-3">
          <div class="icon icon-shape icon-sm bg-gradient-dark shadow text-center border-radius-md me-2">
            <i class="fas fa-list-ol text-white"></i>
          </div>
          <h6 class="mb-0">Year Levels</h6>
        </div>
        <p class="text-sm mb-2">Total: <strong>{{ number_format($levelsTotal) }}</strong></p>
        <p class="text-sm mb-3">Active: <strong>{{ number_format($levelsActive) }}</strong></p>
        <a href="{{ route('scholarship-academic.year-levels.index') }}" class="btn btn-sm bg-gradient-dark mt-auto mb-0">Open Year Levels</a>
      </div>
    </div>
  </div>

  <div class="col-lg-3 mb-4">
    <div class="card h-100">
      <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-center mb-3">
          <div class="icon icon-shape icon-sm bg-gradient-success shadow text-center border-radius-md me-2">
            <i class="fas fa-sitemap text-white"></i>
          </div>
          <h6 class="mb-0">Programs</h6>
        </div>
        <p class="text-sm mb-2">Total: <strong>{{ number_format($programsTotal) }}</strong></p>
        <p class="text-sm mb-3">Active: <strong>{{ number_format($programsActive) }}</strong></p>
        <a href="{{ route('scholarship-academic.programs.index') }}" class="btn btn-sm bg-gradient-success mt-auto mb-0">Open Programs</a>
      </div>
    </div>
  </div>
</div>

@endsection
