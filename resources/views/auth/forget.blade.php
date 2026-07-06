@extends('layouts.app')

@section('title', 'SRIS — Forgot Password')

@push('styles')
    <style>
        /* YOUR CSS STAYS EXACTLY SAME */
    </style>
@endpush

@section('content')
<div class="rv-root">

    {{-- LEFT PANEL --}}
    <div class="rv-left">
        <div class="rv-lines"></div>
        <div class="rv-pulse"></div>

        <div class="rv-brand">
            <div class="rv-brand-icon">
                <i class="fas fa-map-marked-alt"></i>
            </div>
            <div>
                <div class="rv-brand-name">SRIS</div>
                <div class="rv-brand-sub">Spatial Revenue Intelligent System</div>
            </div>
        </div>

        <div class="rv-hero">
            <div class="rv-gold-tag">
                <span class="rv-gold-dot"></span>
                Password Recovery | Secure System
            </div>

            <h1>Reset<br>Your<br><span>Password</span></h1>

            <p>Enter your registered email to receive reset instructions.</p>
        </div>

        <div class="rv-secure">
            <i class="fas fa-shield-alt"></i>
            Secure Reset · Email Verification · 24/7 Support
        </div>
    </div>

    {{-- RIGHT PANEL --}}
    <div class="rv-right">

        <div class="rv-form-wrap">

            <div class="rv-form-eyebrow">SRIS | Password Recovery</div>
            <div class="rv-form-title">Forgot Password</div>
            <div class="rv-form-sub">We'll send you a link to reset your password</div>

            <div class="rv-form-divider"></div>

            <form id="ForgotPasswordForm">
                @csrf

                {{-- EMAIL --}}
                <div class="rv-field">
                    <label class="rv-label">Registered Email Address</label>

                    <div class="rv-input-box">
                        <i class="fas fa-envelope rv-input-icon"></i>
                        <input type="email"
                               id="email"
                               name="email"
                               class="rv-input"
                               placeholder="operator@domain.com"
                               autocomplete="email"
                               autofocus>
                    </div>

                    <div class="rv-alert error d-none" id="email_error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span class="email_msg"></span>
                    </div>

                    <div class="rv-alert success d-none" id="email_success">
                        <i class="fas fa-check-circle"></i>
                        <span class="success_msg"></span>
                    </div>
                </div>

                {{-- BUTTON --}}
                <button type="submit" class="btn rv-submit" id="rvResetBtn">
                    <span class="rv-submit-shimmer"></span>

                    <div class="rv-submit-inner">
                        <span>Send Reset Link</span>
                        <span class="rv-submit-arrow">
                            <i class="fas fa-paper-plane"></i>
                        </span>
                    </div>

                    <div class="rv-spinner"></div>
                </button>

            </form>

            <div class="rv-form-footer">
                Remember your password? <a href="{{ route('login') }}">Back to Login</a>
                <br>
                <small style="font-size: 12px; opacity: 0.7;">Check your spam folder if you don't receive the email</small>
            </div>

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // =========================
    // CLEAR ERRORS
    // =========================
    function clearErrors() {
        $(".rv-alert.error").addClass("d-none");
        $(".email_msg").text("");
        $("#email").removeClass("is-invalid");
    }

    function clearSuccess() {
        $(".rv-alert.success").addClass("d-none");
        $(".success_msg").text("");
    }

    // =========================
    // FORGOT PASSWORD FORM
    // =========================
    $("#ForgotPasswordForm").submit(function (e) {
        e.preventDefault();

        clearErrors();
        clearSuccess();

        let formData = $(this).serialize();

        $.ajax({
            url: "{{ route('sendForget') }}",
            type: "POST",
            data: formData,

            beforeSend: function () {
                $("#rvResetBtn")
                    .prop("disabled", true)
                    .addClass("loading")
                    .html('<i class="fas fa-spinner fa-spin"></i> Sending reset link...');
            },

            success: function (response) {
                // Show success message
                if (response.status || response.message) {
                    $("#email_success").removeClass("d-none");
                    $("#email_success .success_msg").text(response.message || "Password reset link sent to your email!");

                    // Clear the email field
                    $("#email").val("");

                    // Optional: Auto redirect after 3 seconds
                    setTimeout(function() {
                        window.location.href = "{{ route('login') }}";
                    }, 3000);
                } else {
                    Swal.fire({
                        icon: "success",
                        title: "Email Sent",
                        text: response.message || "Password reset link has been sent to your email address.",
                        confirmButtonColor: "#1679AB"
                    }).then(() => {
                        window.location.href = "{{ route('login') }}";
                    });
                }
            },

            error: function (xhr) {
                let message = "Something went wrong. Please try again.";

                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;

                    if (errors.email) {
                        $("#email_error").removeClass("d-none");
                        $("#email_error .email_msg").text(errors.email[0]);
                        $("#email").addClass("is-invalid");
                        message = errors.email[0];
                    }
                } else if (xhr.status === 429) {
                    message = "Too many attempts. Please wait a few minutes before trying again.";
                    Swal.fire({
                        icon: "warning",
                        title: "Rate Limit Exceeded",
                        text: message,
                        confirmButtonColor: "#1679AB"
                    });
                } else if (xhr.responseJSON?.message) {
                    message = xhr.responseJSON.message;
                }

                if (xhr.status !== 422) {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: message,
                        confirmButtonColor: "#1679AB"
                    });
                }
            },

            complete: function () {
                $("#rvResetBtn")
                    .prop("disabled", false)
                    .removeClass("loading")
                    .html(`
                        <span class="rv-submit-shimmer"></span>
                        <div class="rv-submit-inner">
                            <span>Send Reset Link</span>
                            <span class="rv-submit-arrow">
                                <i class="fas fa-paper-plane"></i>
                            </span>
                        </div>
                        <div class="rv-spinner"></div>
                    `);
            }
        });

    });

});
</script>
@endpush
