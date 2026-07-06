@extends('layouts.app')

@section('title', 'SRIS — Create Account')

@push('styles')
    {{-- Your existing CSS is already included --}}
    <link rel="stylesheet" href="{{ asset('css/auth-enhancements.css') }}">
@endpush

@section('content')
    <div class="rv-root">
        {{-- LEFT PANEL with extra icons --}}
        <div class="rv-left">
            <i class="fas fa-globe-asia rv-floating-icon rv-floating-icon-1"></i>
            <i class="fas fa-chart-line rv-floating-icon rv-floating-icon-2"></i>
            <div class="rv-lines"></div>
            <div class="rv-pulse"></div>

            <div class="rv-brand">
                <div class="rv-brand-icon">
                    <i class="fas fa-draw-polygon"></i>
                </div>
                <div>
                    <div class="rv-brand-name">SRIS</div>
                    <div class="rv-brand-sub">Spatial Revenue Intelligence</div>
                </div>
            </div>

            <div class="rv-hero">
                <div class="rv-gold-tag">
                    <i class="fas fa-fingerprint"></i>
                    <span class="rv-gold-dot"></span>
                    Secure Onboarding
                </div>

                <h1>Access<br>Spatial<br><span>Intelligence</span></h1>

                <p><i class="fas fa-map-marker-alt" style="margin-right: 6px;"></i> GIS-powered revenue ecosystem</p>
            </div>

            <div class="rv-secure">
                <i class="fas fa-shield-haltered"></i>
                <span>256-bit Encryption · Role-based Access · Full Audit Trail</span>
            </div>
        </div>

        {{-- RIGHT PANEL (Registration Form with rich icons) --}}
        <div class="rv-right">
            <div class="rv-form-wrap">
                <div class="rv-form-eyebrow">
                    <i class="fas fa-user-plus"></i> Identity Registration
                </div>
                <div class="rv-form-title">Create Profile</div>
                <div class="rv-form-sub">Complete the form below to access SRIS dashboard</div>

                <div class="rv-form-divider">
                    <div class="rv-form-divider-line"></div>
                    <i class="fas fa-id-card rv-form-divider-icon"></i>
                    <div class="rv-form-divider-line"></div>
                </div>

                <form id="RegisterForm" enctype="multipart/form-data">
                    @csrf

                    {{-- Profile Image with enhanced upload zone --}}
                    <div class="rv-field">
                        <label class="rv-label">
                            <i class="fas fa-camera-retro"></i> Profile Image
                        </label>
                        <div class="profile-upload-area" id="uploadArea">
                            <img id="previewImg" src="https://ui-avatars.com/api/?name=👤&background=0A1E3F&color=C5A028&size=100&rounded=true&bold=true"
                                 style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 8px;">
                            <div class="upload-hint">
                                <i class="fas fa-cloud-upload-alt"></i> Click or drag to upload
                            </div>
                            <input type="file" name="profile_image" id="profile_image" accept="image/*" style="display: none;">
                        </div>
                        <div class="rv-alert error d-none" id="profile_image_error">
                            <i class="fas fa-exclamation-triangle"></i> <span class="profile_image_msg"></span>
                        </div>
                    </div>

                    {{-- Full Name with icon --}}
                    <div class="rv-field">
                        <label class="rv-label"><i class="fas fa-user-check"></i> Full Name</label>
                        <div class="rv-input-box">
                            <i class="fas fa-user-circle rv-input-icon"></i>
                            <input type="text" name="name" id="name" class="rv-input" placeholder="e.g., Juan Dela Cruz">
                        </div>
                        <div class="rv-alert error d-none" id="name_error">
                            <i class="fas fa-exclamation-circle"></i> <span class="name_msg"></span>
                        </div>
                    </div>

                    {{-- Email with icon --}}
                    <div class="rv-field">
                        <label class="rv-label"><i class="fas fa-envelope-open-text"></i> Email Address</label>
                        <div class="rv-input-box">
                            <i class="fas fa-at rv-input-icon"></i>
                            <input type="email" name="email" id="email" class="rv-input" placeholder="official@sris.gov.ph">
                        </div>
                        <div class="rv-alert error d-none" id="email_error">
                            <i class="fas fa-envelope-exclamation"></i> <span class="email_msg"></span>
                        </div>
                    </div>

                    {{-- Phone Number with icon --}}
                    <div class="rv-field">
                        <label class="rv-label"><i class="fas fa-mobile-alt"></i> Mobile Number</label>
                        <div class="rv-input-box">
                            <i class="fas fa-phone-alt rv-input-icon"></i>
                            <input type="tel" name="phone" id="phone" class="rv-input" placeholder="09XX XXX XXXX">
                        </div>
                        <div class="rv-alert error d-none" id="phone_error">
                            <i class="fas fa-phone-slash"></i> <span class="phone_msg"></span>
                        </div>
                    </div>

                    {{-- Password with strength meter --}}
                    <div class="rv-field">
                        <label class="rv-label"><i class="fas fa-lock"></i> Create Password</label>
                        <div class="rv-input-box">
                            <i class="fas fa-key rv-input-icon"></i>
                            <input type="password" name="password" id="password" class="rv-input" placeholder="••••••••">
                            <button type="button" class="rv-eye" id="togglePassword">
                                <i class="far fa-eye-slash"></i>
                            </button>
                        </div>
                        <div class="password-strength-meter">
                            <div class="strength-bar-fill" id="strengthFill"></div>
                        </div>
                        <div class="strength-text" id="strengthText">
                            <i class="fas fa-shield-alt"></i> <span>Use 8+ characters with letters & numbers</span>
                        </div>
                        <div class="rv-alert error d-none" id="password_error">
                            <i class="fas fa-lock-open"></i> <span class="password_msg"></span>
                        </div>
                    </div>

                    <button type="submit" class="rv-submit" id="rvRegisterBtn">
                        <div class="rv-submit-inner">
                            <i class="fas fa-user-check"></i>
                            <span>Create SRIS Account</span>
                            <span class="rv-submit-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </span>
                        </div>
                        <div class="rv-spinner"></div>
                    </button>
                </form>

                <div class="rv-form-footer">
                    <i class="fas fa-arrow-circle-left"></i> Already have access?
                    <a href="{{ route('login') }}"><i class="fas fa-sign-in-alt"></i> Login to SRIS</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // ========== ENHANCED PROFILE IMAGE UPLOAD ==========
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('profile_image');

    if(uploadArea) {
        uploadArea.addEventListener('click', () => fileInput.click());
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if(files.length) {
                fileInput.files = files;
                previewImage(files[0]);
            }
        });
    }

    $("#profile_image").on("change", function(e) {
        if(this.files && this.files[0]) previewImage(this.files[0]);
    });

    function previewImage(file) {
        let reader = new FileReader();
        reader.onload = function(e) {
            $("#previewImg").attr("src", e.target.result);
        };
        reader.readAsDataURL(file);
    }

    // Password visibility toggle
    $('#togglePassword').click(function() {
        const passwordField = $('#password');
        const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
        passwordField.attr('type', type);
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });

    // Password strength meter (live)
    $('#password').on('input', function() {
        const val = $(this).val();
        let strength = 0;
        if(val.length >= 8) strength += 25;
        if(/[A-Z]/.test(val)) strength += 25;
        if(/[0-9]/.test(val)) strength += 25;
        if(/[^A-Za-z0-9]/.test(val)) strength += 25;

        $('#strengthFill').css('width', strength + '%');
        let strengthColor = '#DC2626';
        let strengthMsg = 'Weak';
        if(strength >= 75) { strengthColor = '#16A34A'; strengthMsg = 'Strong'; }
        else if(strength >= 50) { strengthColor = '#EAB308'; strengthMsg = 'Medium'; }
        else if(strength >= 25) strengthColor = '#F97316';

        $('#strengthFill').css('background', strengthColor);
        $('#strengthText span').text(strengthMsg === 'Strong' ? 'Excellent password' : (strengthMsg === 'Medium' ? 'Add symbols for better security' : 'Minimum 8 characters + letters/numbers'));
        if(strength > 0) $('#strengthText i').css('color', strengthColor);
    });

    // Clear errors helper
    function clearErrors() {
        $(".rv-alert.error").addClass("d-none");
        $(".rv-input").removeClass("is-invalid");
    }

    // Submit form with AJAX
    $("#RegisterForm").submit(function(e) {
        e.preventDefault();
        clearErrors();
        let formData = new FormData(this);

        $.ajax({
            url: "{{ route('register.submit') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $("#rvRegisterBtn").prop("disabled", true).addClass('loading');
                $("#rvRegisterBtn .rv-submit-inner").hide();
                $("#rvRegisterBtn .rv-spinner").show();
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Account Created',
                    text: response.message || 'Registration successful! Redirecting...',
                    background: '#fff',
                    confirmButtonColor: '#0A1E3F'
                }).then(() => {
                    if(response.redirect) window.location.href = response.redirect;
                });
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        $("#" + key + "_error").removeClass("d-none");
                        $("#" + key + "_error span").text(value[0]);
                        $("#" + key).addClass("is-invalid");
                    });
                    Swal.fire('Validation Error', 'Please check the form fields', 'error');
                } else {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Registration failed', 'error');
                }
            },
            complete: function() {
                $("#rvRegisterBtn").prop("disabled", false).removeClass('loading');
                $("#rvRegisterBtn .rv-submit-inner").show();
                $("#rvRegisterBtn .rv-spinner").hide();
            }
        });
    });
});
</script>
@endpush
