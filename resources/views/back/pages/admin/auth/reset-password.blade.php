@extends('back.layout.auth-layout')
@section('pageTitle', isset($pageTitle) ? $pageTitle : 'Reset password')
@section('content')
    <div class="login-box box-shadow border-radius-10 bg-white">
        <div class="login-title">
            <h2 class="text-primary text-center">Reset Password</h2>
        </div>
        <h6 class="mb-20">Enter your new password, confirm and submit</h6>
        <form action="{{ route('admin.reset-password-handler') }}" method="post">
            @csrf
            @if (Session::get('errMsg'))
                <div class="alert alert-danger">
                    {{ Session::get('errMsg') }}

                    <button class="close" data-dissmiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if (Session::get('success'))
                <div class="alert alert-success">
                    {{ Session::get('success') }}

                    <button class="close" data-dissmiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <input type="hidden" name="token" value="{{ $token }}">

            <div class="input-group custom">
                <input type="password" class="form-control form-control-lg" placeholder="New Password" name="new_password"
                    value="{{ old('new_password') }}">
                <div class="input-group-append custom">
                    <span class="input-group-text"><i class="dw dw-padlock1"></i></span>
                </div>
            </div>

            @error('new_password')
                <div class="d-block text-danger" style="margin-top: -25px; margin-bottom:15px;">
                    {{ $message }}
                </div>
            @enderror

            <div class="input-group custom">
                <input type="password" class="form-control form-control-lg" placeholder="Confirm New Password"
                    name="new_password_confirmation" value="{{ old('new_password_confirmation') }}">
                <div class="input-group-append custom">
                    <span class="input-group-text"><i class="dw dw-padlock1"></i></span>
                </div>
            </div>

            @error('new_password_confirm')
                <div class="d-block text-danger" style="margin-top: -25px; margin-bottom:15px;">
                    {{ $message }}
                </div>
            @enderror

            <div class="row align-items-center">
                <div class="col-5">
                    <div class="input-group mb-0">

                        <input class="btn btn-primary btn-lg btn-block" type="submit" value="Submit">
                        {{--                           
                        <a class="btn btn-primary btn-lg btn-block" href="index.html">Submit</a> --}}
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
