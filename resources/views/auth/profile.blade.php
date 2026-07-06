@extends('layouts.office')

@section('title', 'My Profile')
@section('page_title', 'My Profile')

@section('content')
<div class="row">
    <div class="col-lg-4 mb-4">
        <!-- Profile Card using your existing CSS -->
        <div class="app-profile-card">
            <div class="app-profile-cover"></div>

            <div class="app-profile-content">
                @php
                    $avatarPath = null;
                    if($user->profile && file_exists(public_path($user->profile))) {
                        $avatarPath = asset($user->profile);
                    }
                @endphp

                @if($avatarPath)
                    <img src="{{ $avatarPath }}"
                         alt="{{ $user->name }}"
                         class="app-profile-avatar"
                         onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=1f2937&color=fff&size=85'">
                @else
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=1f2937&color=fff&size=85"
                         alt="{{ $user->name }}"
                         class="app-profile-avatar">
                @endif

                <h3 class="app-profile-name">{{ $user->name }}</h3>

                <div class="app-profile-role">
                    <i class="bi bi-shield-check"></i>
                    <span>{{ ucfirst($user->role) }}</span>
                </div>

                @if($user->email_verified_at)
                    <div class="profile-status">
                        <i class="bi bi-check-circle-fill"></i>
                        Verified Account
                    </div>
                @else
                    <div class="profile-status inactive">
                        <i class="bi bi-clock-fill"></i>
                        Pending Verification
                    </div>
                @endif
            </div>

            <div class="app-profile-info-grid">
                <div class="app-profile-info-card">
                    <i class="bi bi-envelope"></i>
                    <div class="info-details">
                        <span class="info-label">Email</span>
                        <span class="info-value">{{ $user->email }}</span>
                    </div>
                </div>
                <div class="app-profile-info-card">
                    <i class="bi bi-phone"></i>
                    <div class="info-details">
                        <span class="info-label">Phone</span>
                        <span class="info-value">{{ $user->phone ?? 'Not provided' }}</span>
                    </div>
                </div>
                @if($user->corporation_id)
                <div class="app-profile-info-card">
                    <i class="bi bi-building"></i>
                    <div class="info-details">
                        <span class="info-label">Corporation</span>
                        <span class="info-value">{{ $user->corporation->name ?? 'N/A' }}</span>
                    </div>
                </div>
                @endif
                <div class="app-profile-info-card">
                    <i class="bi bi-calendar"></i>
                    <div class="info-details">
                        <span class="info-label">Member Since</span>
                        <span class="info-value">{{ $user->created_at->format('F d, Y') }}</span>
                    </div>
                </div>
            </div>

            <div class="profile-stats-row">
                <div class="profile-stat">
                    <div class="profile-stat-number">{{ $user->created_at->format('Y') }}</div>
                    <div class="profile-stat-label">Joined</div>
                </div>
                <div class="profile-stat">
                    <div class="profile-stat-number">
                        @if($user->is_active)
                            <i class="bi bi-circle-fill text-success" style="font-size: 12px;"></i>
                        @else
                            <i class="bi bi-circle-fill text-danger" style="font-size: 12px;"></i>
                        @endif
                    </div>
                    <div class="profile-stat-label">Status</div>
                </div>
                <div class="profile-stat">
                    <div class="profile-stat-number">{{ $user->role }}</div>
                    <div class="profile-stat-label">Role</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Edit Profile Form -->
        <div class="form-section">
            <div class="form-section-header">
                <h6>
                    <i class="bi bi-pencil-square me-2"></i>
                    Edit Profile Information
                </h6>
            </div>
            <div class="form-section-body">
                <form id="profileForm">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-person me-1"></i> Full Name
                            </label>
                            <div class="rv-input-box">
                                <i class="bi bi-person rv-input-icon"></i>
                                <input type="text" name="name" class="rv-input" value="{{ $user->name }}" required>
                            </div>
                            <div class="invalid-feedback" id="error-name"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-envelope me-1"></i> Email Address
                            </label>
                            <div class="rv-input-box">
                                <i class="bi bi-envelope rv-input-icon"></i>
                                <input type="email" name="email" class="rv-input" value="{{ $user->email }}" required>
                            </div>
                            <div class="invalid-feedback" id="error-email"></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-lock me-1"></i> New Password
                            </label>
                            <div class="rv-input-box">
                                <i class="bi bi-lock rv-input-icon"></i>
                                <input type="password" name="password" class="rv-input" id="password" placeholder="Leave blank to keep current">
                                <button type="button" class="rv-eye" onclick="togglePassword('password')">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="error-password"></div>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-check-circle me-1"></i> Confirm Password
                            </label>
                            <div class="rv-input-box">
                                <i class="bi bi-check-circle rv-input-icon"></i>
                                <input type="password" name="password_confirmation" class="rv-input" id="password_confirmation">
                                <button type="button" class="rv-eye" onclick="togglePassword('password_confirmation')">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-image me-1"></i> Profile Image
                        </label>
                        <div class="profile-upload-area" id="uploadArea">
                            <input type="file" name="profile" id="profileImage" accept="image/*" style="display: none;">
                            <div class="text-center">
                                <i class="bi bi-cloud-upload" style="font-size: 32px; color: var(--accent);"></i>
                                <p class="mt-2 mb-1">Click or drag to upload profile image</p>
                                <small class="upload-hint">JPG, JPEG, PNG (Max 2MB)</small>
                            </div>
                            <div class="image-preview mt-3 text-center" id="imagePreview" style="display: none;">
                                <img id="previewImg" src="" alt="Preview" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
                                <small class="text-muted d-block mt-2">New image preview</small>
                            </div>
                        </div>
                        <div class="invalid-feedback" id="error-profile"></div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="rv-submit" id="updateBtn">
                            <div class="rv-submit-inner">
                                <span>Update Profile</span>
                                <div class="rv-submit-arrow">
                                    <i class="bi bi-arrow-right"></i>
                                </div>
                            </div>
                            <div class="rv-spinner"></div>
                            <div class="rv-submit-shimmer"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Additional Info Card -->
        <div class="form-section mt-4">
            <div class="form-section-header">
                <h6>
                    <i class="bi bi-info-circle me-2"></i>
                    Account Information
                </h6>
            </div>
            <div class="form-section-body">
                <div class="alert alert-info mb-0">
                    <i class="bi bi-shield-check me-2"></i>
                    Your account is {{ $user->is_active ? 'active' : 'inactive' }}.
                    @if(!$user->email_verified_at)
                        <a href="#" class="alert-link">Verify your email</a> to access all features.
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>


$(document).ready(function() {
    // Upload area click
    $('#uploadArea').on('click', function(e) {
        if(e.target !== $('#profileImage')[0]) {
            $('#profileImage').click();
        }
    });

    // Drag and drop
    const uploadArea = document.getElementById('uploadArea');
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        uploadArea.classList.add('dragover');
    }

    function unhighlight() {
        uploadArea.classList.remove('dragover');
    }

    uploadArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        $('#profileImage')[0].files = files;
        handleImagePreview(files[0]);
    }

    // Image preview
    $('#profileImage').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            handleImagePreview(file);
        }
    });

    function handleImagePreview(file) {
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#previewImg').attr('src', e.target.result);
                $('#imagePreview').fadeIn();
            }
            reader.readAsDataURL(file);
        }
    }

    // Form submission
    $('#profileForm').on('submit', function(e) {
        e.preventDefault();

        let formData = new FormData(this);
        let updateBtn = $('#updateBtn');

        $.ajax({
            url: "{{ route('profile.update') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function() {
                updateBtn.addClass('loading');
                updateBtn.prop('disabled', true);
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');
            },
            success: function(response) {
                if(response.status) {
                    showFlashMessage(response.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                }
            },
            error: function(xhr) {
                updateBtn.removeClass('loading');
                updateBtn.prop('disabled', false);

                if(xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        let input = $('[name="' + field + '"]');
                        input.addClass('is-invalid');
                        $('#error-' + field).text(messages[0]);
                    });
                    showFlashMessage('Please fix the validation errors', 'error');
                } else {
                    showFlashMessage(xhr.responseJSON?.message || 'Something went wrong', 'error');
                }
            }
        });
    });
});
</script>
@endpush
