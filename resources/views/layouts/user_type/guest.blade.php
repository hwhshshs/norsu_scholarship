@extends('layouts.app')

@section('guest')
    @yield('content')

    @if(!\Request::is('login/forgot-password'))
        @include('layouts.footers.guest.footer')
    @endif
@endsection