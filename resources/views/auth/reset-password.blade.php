@extends('layouts.app')

@section('title', 'SRIS — Reset Password')

@push('styles')
<style>
    /* Add any additional styles if needed */
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
                Password Reset | Secure System
            </div>

            <h1>Create<br>New<br><span>Password</span></h1>

            <p>Enter your new password to regain access to your account.</p>
        </div>

        <div class="rv-secure">
            <i class="fas fa-shield-alt"></i>
            Secure Reset · Strong Password Required · Encrypted
        </div>
    </div>

    {{-- RIGHT PANEL --}}
    <div class="rv-right">

        <div class="rv-form-wrap">

            <div class="rv-form-eyebrow">SRIS | Password Reset</div>
            <div class="rv-form-title">Reset Password</div>
            <div class="rv-form-sub">Create a new strong password for your account</div>

            <div class="rv-form-divider"></div>

            <form id="ResetPasswordForm">
                @csrf

                <input type="hidden" name="token" id="token" value="{{ $token }}">
                <input type="hidden" name="email" id="email" value="{{ $email }}">

                {{-- NEW PASSWORD --}}
                <div class="rv-field">
                    <label class="rv-label">New Password</label>

                    <div class="rv-input-box">
                        <i class="fas fa-key rv-input-icon"></i>
                        <input type="password"
                               id="password"
                               name="password"
                               class="rv-input"
                               placeholder="••••••••"
                               autocomplete="new-password">
                    </div>

                    <div class="rv-alert error d-none" id="password_error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span class="password_msg"></span>
                    </div>

                    <div class="password-strength d-none" id="passwordStrength">
                        <div class="strength-bar"></div>
                        <small class="strength-text"></small>
                    </div>
                </div>

                {{-- CONFIRM PASSWORD --}}
                <div class="rv-field">
                    <label class="rv-label">Confirm New Password</label>

                    <div class="rv-input-box">
                        <i class="fas fa-key rv-input-icon"></i>
                        <input type="password"
                               id="password_confirmation"
                               name="password_confirmation"
                               class="rv-input"
                               placeholder="Confirm your password"
                               autocomplete="new-password">
                    </div>

                    <div class="rv-alert error d-none" id="password_confirmation_error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span class="password_confirmation_msg"></span>
                    </div>
                </div>

                {{-- BUTTON --}}
                <button type="submit" class="btn rv-submit" id="rvResetBtn">
                    <span class="rv-submit-shimmer"></span>

                    <div class="rv-submit-inner">
                        <span>Reset Password</span>
                        <span class="rv-submit-arrow">
                            <i class="fas fa-check-circle"></i>
                        </span>
                    </div>

                    <div class="rv-spinner"></div>
                </button>

            </form>

            <div class="rv-form-footer">
                Remember your password? <a href="{{ route('login') }}">Back to Login</a>
            </div>

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // Password strength checker
    $('#password').on('keyup', function() {
        const password = $(this).val();
        const strengthBar = $('.strength-bar');
        const strengthText = $('.strength-text');

        if (password.length > 0) {
            $('#passwordStrength').removeClass('d-none');

            let strength = 0;

            // Length check
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;

            // Contains numbers
            if (password.match(/[0-9]/)) strength++;

            // Contains lowercase and uppercase
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;

            // Contains special characters
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            // Update strength bar
            const percentage = (strength / 5) * 100;
            strengthBar.css('width', percentage + '%');

            // Update text and color
            if (strength <= 1) {
                strengthBar.css('background', '#ef4444');
                strengthText.text('Weak password').css('color', '#ef4444');
            } else if (strength <= 3) {
                strengthBar.css('background', '#f59e0b');
                strengthText.text('Medium password').css('color', '#f59e0b');
            } else {
                strengthBar.css('background', '#10b981');
                strengthText.text('Strong password').css('color', '#10b981');
            }
        } else {
            $('#passwordStrength').addClass('d-none');
        }
    });

    // Clear errors function
    function clearErrors() {
        $(".rv-alert.error").addClass("d-none");
        $(".password_msg, .password_confirmation_msg").text("");
        $("#password, #password_confirmation").removeClass("is-invalid");
    }

    // Submit reset password form
    $("#ResetPasswordForm").submit(function (e) {
        e.preventDefault();

        clearErrors();

        let formData = $(this).serialize();

        $.ajax({
            url: "{{ route('password.update') }}",
            type: "POST",
            data: formData,

            beforeSend: function () {
                $("#rvResetBtn")
                    .prop("disabled", true)
                    .addClass("loading")
                    .html('<i class="fas fa-spinner fa-spin"></i> Resetting password...');
            },

            success: function (response) {
                if (response.status) {
                    Swal.fire({
                        icon: "success",
                        title: "Password Reset Successful!",
                        text: response.message,
                        confirmButtonColor: "#1679AB"
                    }).then(() => {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        }
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: response.message,
                        confirmButtonColor: "#1679AB"
                    });
                }
            },

            error: function (xhr) {
                let message = "Something went wrong. Please try again.";

                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;

                    if (errors.email) {
                        message = errors.email[0];
                    } else if (errors.password) {
                        $("#password_error").removeClass("d-none");
                        $("#password_error .password_msg").text(errors.password[0]);
                        $("#password").addClass("is-invalid");
                        message = errors.password[0];
                    } else if (errors.password_confirmation) {
                        $("#password_confirmation_error").removeClass("d-none");
                        $("#password_confirmation_error .password_confirmation_msg").text(errors.password_confirmation[0]);
                        $("#password_confirmation").addClass("is-invalid");
                        message = errors.password_confirmation[0];
                    } else if (errors.token) {
                        message = "Invalid or expired reset link. Please request a new password reset.";
                        Swal.fire({
                            icon: "warning",
                            title: "Link Expired",
                            text: message,
                            confirmButtonColor: "#1679AB"
                        }).then(() => {
                            window.location.href = "{{ route('forgetemail') }}";
                        });
                        return;
                    }
                } else if (xhr.status === 400) {
                    message = xhr.responseJSON.message || "Invalid or expired reset link.";
                    Swal.fire({
                        icon: "warning",
                        title: "Invalid Link",
                        text: message,
                        confirmButtonColor: "#1679AB"
                    }).then(() => {
                        window.location.href = "{{ route('forgetemail') }}";
                    });
                    return;
                } else if (xhr.responseJSON?.message) {
                    message = xhr.responseJSON.message;
                }

                if (xhr.status !== 400) {
                    Swal.fire({
                        icon: "error",
                        title: "Reset Failed",
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
                            <span>Reset Password</span>
                            <span class="rv-submit-arrow">
                                <i class="fas fa-check-circle"></i>
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
